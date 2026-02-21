<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\FinanceCategory;
use App\Models\FinancialTransaction;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaleService
{
    public function __construct(
        protected CommissionService $commissionService,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function createFromPos(array $data, User $user): Sale
    {
        $manualItems = $this->extractManualPosItems(
            Arr::get($data, 'items', []),
        );

        $preparedManualItems = $this->prepareManualItems($manualItems);
        $preparedAutoConsumableItems = $this->prepareAutoConsumableItems($preparedManualItems);
        $preparedItems = [...$preparedManualItems, ...$preparedAutoConsumableItems];

        $cashierId = Arr::get($data, 'cashier_id') ?: $user->employee_id;

        if (! $cashierId) {
            throw ValidationException::withMessages([
                'cashier_id' => 'Kasir wajib diisi.',
            ]);
        }

        $paymentMethodId = Arr::get($data, 'payment_method_id');

        if (! $paymentMethodId) {
            throw ValidationException::withMessages([
                'payment_method_id' => 'Metode pembayaran wajib diisi.',
            ]);
        }

        $paidAt = Arr::get($data, 'paid_at')
            ? Carbon::parse(Arr::get($data, 'paid_at'))
            : now();

        return DB::transaction(function () use ($data, $preparedItems, $cashierId, $paymentMethodId, $paidAt, $user): Sale {
            $subtotal = 0;
            $serviceTotal = 0;
            $retailTotal = 0;

            foreach ($preparedItems as $prepared) {
                $lineTotal = (float) $prepared['line_total'];

                $subtotal += $lineTotal;

                if ($prepared['product']->product_type === 'service') {
                    $serviceTotal += $lineTotal;
                } else {
                    $retailTotal += $lineTotal;
                }
            }

            $discount = (float) ($data['discount'] ?? 0);
            $total = $subtotal - $discount;

            $sale = Sale::create([
                'invoice_no' => $this->generateInvoiceNo(),
                'cashier_id' => $cashierId,
                'customer_name' => $data['customer_name'] ?? null,
                'customer_phone' => $data['customer_phone'] ?? null,
                'payment_method_id' => $paymentMethodId,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'total' => $total,
                'paid_at' => $paidAt,
                'notes' => $data['notes'] ?? null,
                'created_by' => $user->id,
            ]);

            foreach ($preparedItems as $prepared) {
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $prepared['product']->id,
                    'employee_id' => $prepared['employee_id'],
                    'qty' => $prepared['qty'],
                    'unit_price' => $prepared['unit_price'],
                    'price_tier' => $prepared['price_tier'],
                    'line_total' => $prepared['line_total'],
                    'commission_rate' => $prepared['commission_rate'],
                    'commission_amount' => $prepared['commission_amount'],
                    'notes' => $prepared['notes'],
                ]);

                $this->handleInventoryForItem($sale, $prepared['product'], $prepared['qty'], $paidAt);
            }

            $this->createIncomeTransactions($sale, $serviceTotal, $retailTotal);

            return $sale;
        });
    }

    /**
     * @param  array<int, mixed>  $items
     * @return array<int, array<string, mixed>>
     */
    protected function extractManualPosItems(array $items): array
    {
        return collect($items)
            ->filter(fn ($item): bool => is_array($item))
            ->filter(fn (array $item): bool => ($item['line_type'] ?? 'manual') !== 'auto_consumable')
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array{
     *     product: Product,
     *     employee_id: int|null,
     *     qty: int,
     *     price_tier: string,
     *     unit_price: float,
     *     line_total: float,
     *     commission_rate: float,
     *     commission_amount: float,
     *     notes: string|null,
     *     source: string
     * }>
     */
    protected function prepareManualItems(array $items): array
    {
        if (empty($items)) {
            throw ValidationException::withMessages([
                'items' => 'Minimal 1 item diperlukan.',
            ]);
        }

        $productIds = collect($items)
            ->pluck('product_id')
            ->filter()
            ->unique()
            ->values();

        if ($productIds->isEmpty()) {
            throw ValidationException::withMessages([
                'items' => 'Produk wajib dipilih.',
            ]);
        }

        $products = Product::with(['category', 'consumables.consumableProduct'])
            ->whereIn('id', $productIds)
            ->get()
            ->keyBy('id');

        $preparedManualItems = [];

        foreach ($items as $index => $item) {
            $productId = $item['product_id'] ?? null;
            $product = $productId ? $products->get($productId) : null;

            if (! $product) {
                throw ValidationException::withMessages([
                    "items.{$index}.product_id" => 'Produk tidak ditemukan.',
                ]);
            }

            $qty = max(1, (int) ($item['qty'] ?? 1));
            $priceTier = ($item['price_tier'] ?? 'regular') === 'callout' ? 'callout' : 'regular';
            $unitPrice = $this->resolveUnitPrice($product, $priceTier);
            $lineTotal = $unitPrice * $qty;

            $employeeId = $item['employee_id'] ?? null;
            $employee = $employeeId ? Employee::find($employeeId) : null;

            if ($product->product_type === 'service' && ! $employee) {
                throw ValidationException::withMessages([
                    "items.{$index}.employee_id" => 'Pegawai wajib diisi untuk jasa.',
                ]);
            }

            $commissionRate = $this->commissionService->resolveRate($product, $employee, $priceTier);
            $commissionAmount = round($lineTotal * $commissionRate, 0);

            $preparedManualItems[] = [
                'product' => $product,
                'employee_id' => $employee?->id,
                'qty' => $qty,
                'price_tier' => $priceTier,
                'unit_price' => $unitPrice,
                'line_total' => $lineTotal,
                'commission_rate' => $commissionRate,
                'commission_amount' => $commissionAmount,
                'notes' => $item['notes'] ?? null,
                'source' => 'manual',
            ];
        }

        return $preparedManualItems;
    }

    /**
     * @param  array<int, array{
     *     product: Product,
     *     employee_id: int|null,
     *     qty: int,
     *     price_tier: string,
     *     unit_price: float,
     *     line_total: float,
     *     commission_rate: float,
     *     commission_amount: float,
     *     notes: string|null,
     *     source: string
     * }>  $preparedManualItems
     * @return array<int, array{
     *     product: Product,
     *     employee_id: int|null,
     *     qty: int,
     *     price_tier: string,
     *     unit_price: float,
     *     line_total: float,
     *     commission_rate: float,
     *     commission_amount: float,
     *     notes: string|null,
     *     source: string
     * }>
     */
    protected function prepareAutoConsumableItems(array $preparedManualItems): array
    {
        $preparedAutoItems = [];

        foreach ($preparedManualItems as $index => $prepared) {
            $serviceProduct = $prepared['product'];

            if ($serviceProduct->product_type !== 'service') {
                continue;
            }

            foreach ($serviceProduct->consumables as $consumable) {
                $consumableProduct = $consumable->consumableProduct;

                if (! $consumableProduct) {
                    throw ValidationException::withMessages([
                        "items.{$index}.product_id" => "Mapping consumable untuk {$serviceProduct->product_name} tidak valid.",
                    ]);
                }

                if (! $consumableProduct->is_active) {
                    throw ValidationException::withMessages([
                        "items.{$index}.product_id" => "Consumable {$consumableProduct->product_name} sedang nonaktif.",
                    ]);
                }

                if ($consumableProduct->product_type !== 'consumable') {
                    throw ValidationException::withMessages([
                        "items.{$index}.product_id" => "{$consumableProduct->product_name} harus bertipe consumable.",
                    ]);
                }

                $qtyPerUnit = (int) $consumable->qty_per_unit;

                if ($qtyPerUnit <= 0) {
                    throw ValidationException::withMessages([
                        "items.{$index}.product_id" => "Qty per unit consumable {$consumableProduct->product_name} harus lebih besar dari 0.",
                    ]);
                }

                $unitPrice = (float) $consumableProduct->product_price;

                if ($unitPrice <= 0) {
                    throw ValidationException::withMessages([
                        "items.{$index}.product_id" => "Harga consumable {$consumableProduct->product_name} belum valid.",
                    ]);
                }

                $qty = $prepared['qty'] * $qtyPerUnit;

                $preparedAutoItems[] = [
                    'product' => $consumableProduct,
                    'employee_id' => null,
                    'qty' => $qty,
                    'price_tier' => 'regular',
                    'unit_price' => $unitPrice,
                    'line_total' => $unitPrice * $qty,
                    'commission_rate' => 0.0,
                    'commission_amount' => 0.0,
                    'notes' => "Auto consumable dari {$serviceProduct->product_name}",
                    'source' => 'auto_consumable',
                ];
            }
        }

        return $preparedAutoItems;
    }

    protected function resolveUnitPrice(Product $product, string $priceTier): float
    {
        if ($priceTier === 'callout' && $product->product_price_other) {
            return (float) $product->product_price_other;
        }

        return (float) $product->product_price;
    }

    protected function handleInventoryForItem(Sale $sale, Product $product, int $qty, Carbon $paidAt): void
    {
        if (! $product->track_stock) {
            return;
        }

        InventoryMovement::create([
            'product_id' => $product->id,
            'type' => 'out',
            'qty' => $qty,
            'unit_cost' => $product->cost_price,
            'reference_type' => Sale::class,
            'reference_id' => $sale->id,
            'occurred_at' => $paidAt,
            'notes' => 'Sale ' . $sale->invoice_no,
        ]);
    }

    protected function createIncomeTransactions(Sale $sale, float $serviceTotal, float $retailTotal): void
    {
        $incomeServiceCategory = FinanceCategory::where('name', 'Pendapatan Jasa')
            ->where('type', 'income')
            ->first();

        $incomeRetailCategory = FinanceCategory::where('name', 'Pendapatan Barang')
            ->where('type', 'income')
            ->first();

        if ($serviceTotal > 0 && $incomeServiceCategory) {
            FinancialTransaction::create([
                'type' => 'income',
                'category_id' => $incomeServiceCategory->id,
                'amount' => $serviceTotal,
                'payment_method_id' => $sale->payment_method_id,
                'occurred_at' => $sale->paid_at,
                'description' => 'Penjualan jasa ' . $sale->invoice_no,
                'reference_type' => Sale::class,
                'reference_id' => $sale->id,
                'created_by' => $sale->created_by,
            ]);
        }

        if ($retailTotal > 0 && $incomeRetailCategory) {
            FinancialTransaction::create([
                'type' => 'income',
                'category_id' => $incomeRetailCategory->id,
                'amount' => $retailTotal,
                'payment_method_id' => $sale->payment_method_id,
                'occurred_at' => $sale->paid_at,
                'description' => 'Penjualan barang ' . $sale->invoice_no,
                'reference_type' => Sale::class,
                'reference_id' => $sale->id,
                'created_by' => $sale->created_by,
            ]);
        }
    }

    protected function generateInvoiceNo(): string
    {
        $datePart = now()->format('Ymd');

        do {
            $random = str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);
            $invoiceNo = "INV-{$datePart}-{$random}";
        } while (Sale::where('invoice_no', $invoiceNo)->exists());

        return $invoiceNo;
    }
}
