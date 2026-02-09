<?php

namespace App\Filament\Pages;

use App\Models\Employee;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Services\SaleService;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Forms\Components\DateTimePicker;
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

class PosPage extends Page
{
    use InteractsWithForms;
    use HasPageShield;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-computer-desktop';

    protected static string|\UnitEnum|null $navigationGroup = 'POS';

    protected static ?string $title = 'POS Kasir';

    protected string $view = 'filament.pages.pos-page';

    public ?array $data = [];

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
                    'qty' => 1,
                    'price_tier' => 'regular',
                ],
            ],
        ]);
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
                        ->schema([
                            Select::make('product_id')
                                ->label('Produk')
                                ->options(fn() => Product::where('is_active', true)->pluck('product_name', 'id'))
                                ->searchable()
                                ->live()
                                ->required()
                                ->afterStateUpdated(function (Set $set, Get $get, ?string $state): void {
                                    $priceTier = (string) ($get('price_tier') ?? 'regular');
                                    $set('unit_price', $this->resolveUnitPriceForForm($state, $priceTier));
                                }),
                            Select::make('employee_id')
                                ->label('Pegawai')
                                ->options(fn() => Employee::where('is_active', true)->pluck('emp_name', 'id'))
                                ->searchable(),
                            Select::make('price_tier')
                                ->label('Harga')
                                ->options([
                                    'regular' => 'Reguler',
                                    'callout' => 'Panggilan',
                                ])
                                ->default('regular')
                                ->live()
                                ->afterStateUpdated(function (Set $set, Get $get): void {
                                    $productId = $get('product_id');
                                    $priceTier = (string) ($get('price_tier') ?? 'regular');
                                    $set('unit_price', $this->resolveUnitPriceForForm($productId, $priceTier));
                                }),
                            TextInput::make('qty')
                                ->label('Qty')
                                ->numeric()
                                ->default(1)
                                ->required(),
                            TextInput::make('unit_price')
                                ->label('Harga Satuan')
                                ->numeric()
                                ->disabled()
                                ->dehydrated(),
                            TextInput::make('notes')
                                ->label('Catatan'),
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
}
