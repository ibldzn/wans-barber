<?php

namespace App\Services;

use App\Models\FinanceCategory;
use App\Models\FinancialTransaction;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Purchase;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;

class PurchaseService
{
    public function syncInventoryAndLedger(Purchase $purchase): void
    {
        $purchase->loadMissing('items.product');

        DB::transaction(function () use ($purchase): void {
            $totalAmount = 0;

            InventoryMovement::query()
                ->where('reference_type', Purchase::class)
                ->where('reference_id', $purchase->id)
                ->delete();

            FinancialTransaction::query()
                ->where('reference_type', Purchase::class)
                ->where('reference_id', $purchase->id)
                ->delete();

            if ($purchase->items->isEmpty()) {
                if ((float) $purchase->total_amount !== 0.0) {
                    $purchase->update(['total_amount' => 0]);
                }

                return;
            }

            foreach ($purchase->items as $item) {
                if (! $item->product || ! $this->isPurchasableProduct($item->product)) {
                    throw ValidationException::withMessages([
                        "items.{$item->id}.product_id" => 'Produk service atau non-stock tidak bisa dibeli lewat modul pembelian.',
                    ]);
                }

                $totalAmount += (float) $item->subtotal;

                InventoryMovement::create([
                    'product_id' => $item->product_id,
                    'type' => 'in',
                    'qty' => $item->qty,
                    'unit_cost' => $item->unit_cost,
                    'reference_type' => Purchase::class,
                    'reference_id' => $purchase->id,
                    'occurred_at' => $purchase->purchased_at->startOfDay(),
                    'notes' => 'Purchase #' . $purchase->id,
                ]);
            }

            if ($purchase->total_amount != $totalAmount) {
                $purchase->update(['total_amount' => $totalAmount]);
            }

            $inventoryCategory = FinanceCategory::where('name', 'Inventory')
                ->where('type', 'expense')
                ->first();

            if ($inventoryCategory) {
                FinancialTransaction::updateOrCreate(
                    [
                        'reference_type' => Purchase::class,
                        'reference_id' => $purchase->id,
                    ],
                    [
                        'type' => 'expense',
                        'category_id' => $inventoryCategory->id,
                        'amount' => $totalAmount,
                        'payment_method_id' => null,
                        'occurred_at' => $purchase->purchased_at->startOfDay(),
                        'description' => 'Pembelian stok',
                        'created_by' => $purchase->created_by,
                    ]
                );
            }
        });
    }

    protected function isPurchasableProduct(Product $product): bool
    {
        return $product->is_active
            && $product->track_stock
            && in_array($product->product_type, ['retail', 'consumable'], true);
    }
}
