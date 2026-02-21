<?php

namespace App\Filament\Pages;

use App\Models\Employee;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Services\SaleService;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class PosPage extends Page
{
    use InteractsWithForms;
    use HasPageShield;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-computer-desktop';

    protected static string|\UnitEnum|null $navigationGroup = 'POS';

    protected static ?string $title = 'POS Kasir';

    protected string $view = 'filament.pages.pos-page';

    public ?array $data = [];
    protected bool $isSyncingItems = false;

    public function mount(): void
    {
        $cashMethod = PaymentMethod::where('method_name', 'Cash')->first();

        $this->form->fill([
            'payment_method_id' => $cashMethod?->id,
            'cashier_id' => auth()->user()?->employee_id,
            'paid_at' => now(),
            'discount' => 0,
            'items' => [
                [
                    'line_type' => 'manual',
                    'parent_signature' => null,
                    'is_locked' => false,
                    'qty' => 1,
                    'price_tier' => 'regular',
                ],
            ],
        ]);

        $this->syncItemsWithAutoConsumables();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Transaksi')->schema([
                    Select::make('cashier_id')
                        ->label('Kasir')
                        ->options(fn() => Employee::where('is_active', true)->pluck('emp_name', 'id'))
                        ->searchable()
                        ->required(),
                    Select::make('payment_method_id')
                        ->label('Metode Pembayaran')
                        ->options(fn() => PaymentMethod::where('is_active', true)->pluck('method_name', 'id'))
                        ->searchable()
                        ->required(),
                    TextInput::make('customer_name')
                        ->label('Nama Customer'),
                    TextInput::make('customer_phone')
                        ->label('No HP'),
                    TextInput::make('discount')
                        ->label('Diskon')
                        ->numeric()
                        ->live()
                        ->default(0),
                    DateTimePicker::make('paid_at')
                        ->label('Tanggal')
                        ->default(now())
                        ->required(),
                    TextInput::make('notes')
                        ->label('Catatan'),
                ])
                    ->columnSpanFull()
                    ->inlineLabel()
                    ->columns(1),
                Section::make('Items')->schema([
                    Repeater::make('items')
                        ->live()
                        ->afterStateUpdated(function (?array $state): void {
                            $this->syncItemsWithAutoConsumables($state);
                        })
                        ->deleteAction(function (Action $action): Action {
                            return $action->visible(function (array $arguments, Repeater $component): bool {
                                $items = $component->getRawState();
                                $item = $items[$arguments['item']] ?? [];

                                return ($item['line_type'] ?? 'manual') !== 'auto_consumable';
                            });
                        })
                        ->schema([
                            Hidden::make('line_type')
                                ->default('manual')
                                ->dehydrated(true),
                            Hidden::make('parent_signature')
                                ->dehydrated(true),
                            Hidden::make('is_locked')
                                ->default(false)
                                ->dehydrated(true),
                            Select::make('product_id')
                                ->label('Produk')
                                ->options(fn() => Product::where('is_active', true)->pluck('product_name', 'id'))
                                ->searchable()
                                ->disabled(fn (Get $get): bool => $this->isLockedLine($get))
                                ->live()
                                ->required(fn (Get $get): bool => ! $this->isLockedLine($get))
                                ->afterStateUpdated(function (Set $set, Get $get, ?string $state): void {
                                    $priceTier = (string) ($get('price_tier') ?? 'regular');
                                    $set('unit_price', $this->resolveUnitPriceForForm($state, $priceTier));
                                }),
                            Select::make('employee_id')
                                ->label('Pegawai')
                                ->options(fn() => Employee::where('is_active', true)->pluck('emp_name', 'id'))
                                ->disabled(fn (Get $get): bool => $this->isLockedLine($get))
                                ->searchable(),
                            Select::make('price_tier')
                                ->label('Harga')
                                ->options([
                                    'regular' => 'Reguler',
                                    'callout' => 'Panggilan',
                                ])
                                ->default('regular')
                                ->disabled(fn (Get $get): bool => $this->isLockedLine($get))
                                ->live()
                                ->afterStateUpdated(function (Set $set, Get $get): void {
                                    $productId = $get('product_id');
                                    $priceTier = (string) ($get('price_tier') ?? 'regular');
                                    $set('unit_price', $this->resolveUnitPriceForForm($productId, $priceTier));
                                }),
                            TextInput::make('qty')
                                ->label('Qty')
                                ->numeric()
                                ->disabled(fn (Get $get): bool => $this->isLockedLine($get))
                                ->live()
                                ->default(1)
                                ->required(fn (Get $get): bool => ! $this->isLockedLine($get)),
                            TextInput::make('unit_price')
                                ->label('Harga Satuan')
                                ->numeric()
                                ->disabled()
                                ->dehydrated(),
                            TextInput::make('notes')
                                ->label('Catatan')
                                ->disabled(fn (Get $get): bool => $this->isLockedLine($get)),
                        ])
                        ->columns(6)
                        ->defaultItems(1)
                        ->addActionLabel('Tambah Item'),
                ]),
            ])
            ->statePath('data');
    }

    public function submit(): void
    {
        $data = $this->form->getState();

        $sale = app(SaleService::class)->createFromPos($data, auth()->user());

        Notification::make()
            ->title('Transaksi berhasil')
            ->body('Invoice: ' . $sale->invoice_no)
            ->success()
            ->send();

        $this->mount();
    }

    protected function resolveUnitPriceForForm(?string $productId, string $priceTier): float
    {
        if (! $productId) {
            return 0.0;
        }

        $product = Product::find($productId);

        if (! $product) {
            return 0.0;
        }

        if ($priceTier === 'callout' && $product->product_price_other) {
            return (float) $product->product_price_other;
        }

        return (float) $product->product_price;
    }

    /**
     * @param  array<int, mixed>|null  $items
     */
    protected function syncItemsWithAutoConsumables(?array $items = null): void
    {
        if ($this->isSyncingItems) {
            return;
        }

        $state = is_array($items) ? $items : ($this->data['items'] ?? []);
        $manualItems = $this->extractManualItems($state);
        $products = $this->getProductsForSync($manualItems);

        $syncedItems = [];

        foreach ($manualItems as $itemKey => $manualItem) {
            $manual = $this->normalizeManualItem($manualItem);
            $manual['unit_price'] = $this->resolveUnitPriceForForm(
                $manual['product_id'] ? (string) $manual['product_id'] : null,
                $manual['price_tier'],
            );

            $syncedItems[$itemKey] = $manual;

            $serviceProduct = $manual['product_id'] ? $products->get($manual['product_id']) : null;

            if (! $serviceProduct || $serviceProduct->product_type !== 'service') {
                continue;
            }

            $parentSignature = $this->buildParentSignature($manual, $itemKey);

            foreach ($serviceProduct->consumables->values() as $mappingIndex => $mapping) {
                $consumableProduct = $mapping->consumableProduct;

                if (! $consumableProduct) {
                    continue;
                }

                $qtyPerUnit = max(0, (int) $mapping->qty_per_unit);

                if ($qtyPerUnit <= 0) {
                    continue;
                }

                $autoRowKey = sprintf(
                    'auto_%s_%s_%d',
                    $itemKey,
                    $consumableProduct->id,
                    $mappingIndex,
                );

                $syncedItems[$autoRowKey] = [
                    'line_type' => 'auto_consumable',
                    'parent_signature' => $parentSignature,
                    'is_locked' => true,
                    'product_id' => $consumableProduct->id,
                    'employee_id' => null,
                    'price_tier' => 'regular',
                    'qty' => $manual['qty'] * $qtyPerUnit,
                    'unit_price' => (float) $consumableProduct->product_price,
                    'notes' => "Auto consumable dari {$serviceProduct->product_name}",
                ];
            }
        }

        if ($state === $syncedItems) {
            return;
        }

        $this->isSyncingItems = true;

        try {
            $this->data['items'] = $syncedItems;
        } finally {
            $this->isSyncingItems = false;
        }
    }

    /**
     * @param  array<int, mixed>  $items
     * @return array<string, array<string, mixed>>
     */
    protected function extractManualItems(array $items): array
    {
        $manualItems = [];

        foreach ($items as $itemKey => $item) {
            if (! is_array($item)) {
                continue;
            }

            if (($item['line_type'] ?? 'manual') === 'auto_consumable') {
                continue;
            }

            $manualItems[(string) $itemKey] = $item;
        }

        if ($manualItems !== []) {
            return $manualItems;
        }

        return [(string) Str::uuid() => [
            'line_type' => 'manual',
            'parent_signature' => null,
            'is_locked' => false,
            'product_id' => null,
            'employee_id' => null,
            'price_tier' => 'regular',
            'qty' => 1,
            'unit_price' => 0,
            'notes' => null,
        ]];
    }

    /**
     * @return array{
     *     line_type: string,
     *     parent_signature: string|null,
     *     is_locked: bool,
     *     product_id: int|null,
     *     employee_id: int|null,
     *     price_tier: string,
     *     qty: int,
     *     unit_price: float,
     *     notes: string|null
     * }
     */
    protected function normalizeManualItem(array $item): array
    {
        $productId = (int) ($item['product_id'] ?? 0);
        $employeeId = (int) ($item['employee_id'] ?? 0);

        return [
            'line_type' => 'manual',
            'parent_signature' => null,
            'is_locked' => false,
            'product_id' => $productId > 0 ? $productId : null,
            'employee_id' => $employeeId > 0 ? $employeeId : null,
            'price_tier' => ($item['price_tier'] ?? 'regular') === 'callout' ? 'callout' : 'regular',
            'qty' => max(1, (int) ($item['qty'] ?? 1)),
            'unit_price' => (float) ($item['unit_price'] ?? 0),
            'notes' => filled($item['notes'] ?? null) ? (string) $item['notes'] : null,
        ];
    }

    /**
     * @param  array{
     *     product_id: int|null,
     *     qty: int,
     *     price_tier: string,
     *     employee_id: int|null
     * }  $manualItem
     */
    protected function buildParentSignature(array $manualItem, string $itemKey): string
    {
        $parts = [
            $itemKey,
            $manualItem['product_id'] ?? 0,
            $manualItem['qty'] ?? 1,
            $manualItem['price_tier'] ?? 'regular',
            $manualItem['employee_id'] ?? 0,
        ];

        return sha1(implode('|', $parts));
    }

    protected function isLockedLine(Get $get): bool
    {
        return (bool) ($get('is_locked') ?? false)
            || ($get('line_type') ?? 'manual') === 'auto_consumable';
    }

    /**
     * @param  array<int, array<string, mixed>>  $manualItems
     * @return \Illuminate\Support\Collection<int, Product>
     */
    protected function getProductsForSync(array $manualItems): \Illuminate\Support\Collection
    {
        $productIds = collect($manualItems)
            ->pluck('product_id')
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values();

        if ($productIds->isEmpty()) {
            return collect();
        }

        return Product::query()
            ->with(['consumables.consumableProduct'])
            ->whereIn('id', $productIds)
            ->get()
            ->keyBy('id');
    }
}
