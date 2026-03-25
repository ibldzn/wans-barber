<x-filament-panels::page>
    @php
        /** @var \App\Models\Sale $sale */
        $sale = $this->getRecord();
        $items = $sale->items;
    @endphp

    <style>
        .sale-view-layout {
            --sale-card-bg: #ffffff;
            --sale-card-border: rgba(15, 23, 42, 0.08);
            --sale-card-shadow: 0 16px 40px rgba(15, 23, 42, 0.06);
            --sale-card-head-border: rgba(15, 23, 42, 0.08);
            --sale-hero-border: rgba(15, 23, 42, 0.08);
            --sale-hero-accent: rgba(245, 158, 11, 0.18);
            --sale-hero-orb: rgba(251, 191, 36, 0.14);
            --sale-hero-bg:
                radial-gradient(circle at top right, rgba(245, 158, 11, 0.18), transparent 28%),
                linear-gradient(135deg, #f8fafc, #eef2ff 55%, #fff7ed);
            --sale-hero-kicker: #64748b;
            --sale-hero-title: #0f172a;
            --sale-hero-subtitle: #475569;
            --sale-hero-stat-bg: rgba(255, 255, 255, 0.58);
            --sale-hero-stat-border: rgba(148, 163, 184, 0.2);
            --sale-hero-stat-label: #64748b;
            --sale-hero-stat-value: #0f172a;
            --sale-hero-total-bg: rgba(255, 255, 255, 0.74);
            --sale-hero-total-border: rgba(245, 158, 11, 0.24);
            --sale-hero-total-label: #64748b;
            --sale-hero-total-value: #0f172a;
            --sale-text-primary: #0f172a;
            --sale-text-secondary: #334155;
            --sale-text-muted: #64748b;
            --sale-table-border: rgba(148, 163, 184, 0.22);
            --sale-empty-bg: #f8fafc;
            --sale-empty-text: #64748b;
            --sale-badge-muted-text: #475569;
            --sale-badge-muted-bg: rgba(148, 163, 184, 0.14);
            --sale-badge-muted-border: rgba(148, 163, 184, 0.24);
            --sale-badge-service-text: #92400e;
            --sale-badge-service-bg: rgba(245, 158, 11, 0.16);
            --sale-badge-service-border: rgba(245, 158, 11, 0.3);
            --sale-badge-retail-text: #1d4ed8;
            --sale-badge-retail-bg: rgba(59, 130, 246, 0.14);
            --sale-badge-retail-border: rgba(59, 130, 246, 0.24);
            --sale-badge-consumable-text: #166534;
            --sale-badge-consumable-bg: rgba(34, 197, 94, 0.14);
            --sale-badge-consumable-border: rgba(34, 197, 94, 0.24);
            display: grid;
            gap: 1.5rem;
        }

        :root.dark .sale-view-layout,
        .dark .sale-view-layout {
            --sale-card-bg: rgba(17, 24, 39, 0.48);
            --sale-card-border: rgba(255, 255, 255, 0.08);
            --sale-card-shadow: none;
            --sale-card-head-border: rgba(255, 255, 255, 0.08);
            --sale-hero-border: rgba(255, 255, 255, 0.08);
            --sale-hero-accent: rgba(245, 158, 11, 0.22);
            --sale-hero-orb: rgba(251, 191, 36, 0.12);
            --sale-hero-bg:
                radial-gradient(circle at top right, rgba(245, 158, 11, 0.22), transparent 28%),
                linear-gradient(135deg, rgba(17, 24, 39, 0.98), rgba(31, 41, 55, 0.96));
            --sale-hero-kicker: rgba(255, 255, 255, 0.68);
            --sale-hero-title: #ffffff;
            --sale-hero-subtitle: rgba(255, 255, 255, 0.72);
            --sale-hero-stat-bg: rgba(255, 255, 255, 0.04);
            --sale-hero-stat-border: rgba(255, 255, 255, 0.06);
            --sale-hero-stat-label: rgba(255, 255, 255, 0.64);
            --sale-hero-stat-value: #ffffff;
            --sale-hero-total-bg: rgba(17, 24, 39, 0.45);
            --sale-hero-total-border: rgba(251, 191, 36, 0.25);
            --sale-hero-total-label: rgba(255, 255, 255, 0.68);
            --sale-hero-total-value: #ffffff;
            --sale-text-primary: #ffffff;
            --sale-text-secondary: rgba(255, 255, 255, 0.9);
            --sale-text-muted: rgba(255, 255, 255, 0.62);
            --sale-table-border: rgba(255, 255, 255, 0.08);
            --sale-empty-bg: rgba(255, 255, 255, 0.03);
            --sale-empty-text: rgba(255, 255, 255, 0.62);
            --sale-badge-muted-text: rgba(255, 255, 255, 0.72);
            --sale-badge-muted-bg: rgba(255, 255, 255, 0.06);
            --sale-badge-muted-border: rgba(255, 255, 255, 0.08);
            --sale-badge-service-text: #fef3c7;
            --sale-badge-service-bg: rgba(245, 158, 11, 0.15);
            --sale-badge-service-border: rgba(245, 158, 11, 0.28);
            --sale-badge-retail-text: #bfdbfe;
            --sale-badge-retail-bg: rgba(59, 130, 246, 0.14);
            --sale-badge-retail-border: rgba(59, 130, 246, 0.24);
            --sale-badge-consumable-text: #c7f9cc;
            --sale-badge-consumable-bg: rgba(34, 197, 94, 0.14);
            --sale-badge-consumable-border: rgba(34, 197, 94, 0.24);
        }

        .sale-view-hero {
            position: relative;
            overflow: hidden;
            border: 1px solid var(--sale-hero-border);
            border-radius: 1.5rem;
            padding: 1.5rem;
            background: var(--sale-hero-bg);
            box-shadow: var(--sale-card-shadow);
        }

        .sale-view-hero::after {
            content: '';
            position: absolute;
            inset: auto -2rem -2rem auto;
            width: 10rem;
            height: 10rem;
            border-radius: 999px;
            background: var(--sale-hero-orb);
            filter: blur(8px);
        }

        .sale-view-hero-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1.25rem;
        }

        .sale-view-kicker {
            margin: 0 0 0.5rem;
            color: var(--sale-hero-kicker);
            font-size: 0.8rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .sale-view-title {
            margin: 0;
            color: var(--sale-hero-title);
            font-size: clamp(1.8rem, 3vw, 2.5rem);
            line-height: 1;
            font-weight: 800;
        }

        .sale-view-subtitle {
            margin: 0.75rem 0 0;
            color: var(--sale-hero-subtitle);
            font-size: 0.95rem;
        }

        .sale-view-total {
            min-width: 13rem;
            padding: 1rem 1.1rem;
            border-radius: 1rem;
            border: 1px solid var(--sale-hero-total-border);
            background: var(--sale-hero-total-bg);
            backdrop-filter: blur(8px);
            text-align: right;
        }

        .sale-view-total-label {
            margin: 0;
            color: var(--sale-hero-total-label);
            font-size: 0.82rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .sale-view-total-value {
            margin: 0.35rem 0 0;
            color: var(--sale-hero-total-value);
            font-size: 2rem;
            line-height: 1;
            font-weight: 800;
        }

        .sale-view-stat-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 1rem;
        }

        .sale-view-stat {
            padding: 1rem 1.1rem;
            border-radius: 1rem;
            background: var(--sale-hero-stat-bg);
            border: 1px solid var(--sale-hero-stat-border);
            backdrop-filter: blur(8px);
        }

        .sale-view-stat-label {
            margin: 0;
            color: var(--sale-hero-stat-label);
            font-size: 0.82rem;
        }

        .sale-view-stat-value {
            margin: 0.45rem 0 0;
            color: var(--sale-hero-stat-value);
            font-size: 1.25rem;
            font-weight: 700;
        }

        .sale-view-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.6fr) minmax(18rem, 0.9fr);
            gap: 1.5rem;
            align-items: start;
        }

        .sale-view-stack {
            display: grid;
            gap: 1.5rem;
        }

        .sale-view-card {
            border: 1px solid var(--sale-card-border);
            border-radius: 1.25rem;
            background: var(--sale-card-bg);
            box-shadow: var(--sale-card-shadow);
            overflow: hidden;
        }

        .sale-view-card-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 1.1rem 1.25rem;
            border-bottom: 1px solid var(--sale-card-head-border);
        }

        .sale-view-card-title {
            margin: 0;
            font-size: 1rem;
            font-weight: 700;
            color: var(--sale-text-primary);
        }

        .sale-view-card-body {
            padding: 1.25rem;
        }

        .sale-view-table-wrap {
            overflow-x: auto;
        }

        .sale-view-table {
            width: 100%;
            border-collapse: collapse;
        }

        .sale-view-table th,
        .sale-view-table td {
            padding: 0.9rem 0.8rem;
            border-bottom: 1px solid var(--sale-table-border);
            vertical-align: top;
        }

        .sale-view-table th {
            color: var(--sale-text-muted);
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-weight: 700;
            white-space: nowrap;
        }

        .sale-view-table tr:last-child td {
            border-bottom: none;
        }

        .sale-view-table td {
            color: var(--sale-text-secondary);
            font-size: 0.95rem;
        }

        .sale-view-product {
            display: grid;
            gap: 0.45rem;
        }

        .sale-view-product-name {
            font-weight: 700;
            color: var(--sale-text-primary);
        }

        .sale-view-product-note {
            color: var(--sale-text-muted);
            font-size: 0.84rem;
            line-height: 1.45;
        }

        .sale-view-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.28rem 0.62rem;
            border-radius: 999px;
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            white-space: nowrap;
            text-transform: uppercase;
        }

        .sale-view-badge-service {
            color: var(--sale-badge-service-text);
            background: var(--sale-badge-service-bg);
            border: 1px solid var(--sale-badge-service-border);
        }

        .sale-view-badge-retail {
            color: var(--sale-badge-retail-text);
            background: var(--sale-badge-retail-bg);
            border: 1px solid var(--sale-badge-retail-border);
        }

        .sale-view-badge-consumable {
            color: var(--sale-badge-consumable-text);
            background: var(--sale-badge-consumable-bg);
            border: 1px solid var(--sale-badge-consumable-border);
        }

        .sale-view-badge-muted {
            color: var(--sale-badge-muted-text);
            background: var(--sale-badge-muted-bg);
            border: 1px solid var(--sale-badge-muted-border);
        }

        .sale-view-meta-grid {
            display: grid;
            gap: 1rem;
        }

        .sale-view-meta-row {
            display: block;
        }

        .sale-view-meta-row > div {
            width: 100%;
        }

        .sale-view-meta-label {
            margin: 0;
            color: var(--sale-text-muted);
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .sale-view-meta-value {
            display: block;
            width: 100%;
            margin: 0.3rem 0 0;
            color: var(--sale-text-primary);
            font-size: 0.96rem;
            font-weight: 600;
            text-align: left;
        }

        .sale-view-divider {
            height: 1px;
            background: var(--sale-table-border);
        }

        .sale-view-summary-row {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            gap: 1rem;
            padding: 0.55rem 0;
        }

        .sale-view-summary-row strong {
            color: var(--sale-text-primary);
            font-size: 1.15rem;
        }

        .sale-view-summary-row span {
            color: var(--sale-text-secondary);
        }

        .sale-view-empty {
            padding: 1.25rem;
            border-radius: 1rem;
            background: var(--sale-empty-bg);
            color: var(--sale-empty-text);
            text-align: center;
        }

        @media (max-width: 1024px) {
            .sale-view-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .sale-view-hero-top {
                flex-direction: column;
            }

            .sale-view-total {
                width: 100%;
                min-width: 0;
                text-align: left;
            }

            .sale-view-stat-grid {
                grid-template-columns: 1fr;
            }

            .sale-view-meta-row,
            .sale-view-summary-row {
                flex-direction: column;
                gap: 0.35rem;
                align-items: flex-start;
            }

            .sale-view-meta-value,
            .sale-view-summary-row strong,
            .sale-view-summary-row span {
                text-align: left;
            }
        }
    </style>

    <div class="sale-view-layout">
        <section class="sale-view-hero">
            <div class="sale-view-hero-top">
                <div>
                    <p class="sale-view-kicker">Invoice {{ $sale->invoice_no }}</p>
                    <h2 class="sale-view-title">{{ $sale->customer_name ?: 'Walk-in Customer' }}</h2>
                    <p class="sale-view-subtitle">
                        {{ $sale->paid_at?->format('d M Y, H:i') }}
                        · {{ $sale->paymentMethod?->method_name ?: 'Tanpa metode' }}
                        · Kasir {{ $sale->cashier?->emp_name ?: '-' }}
                    </p>
                </div>

                <div class="sale-view-total">
                    <p class="sale-view-total-label">Total Transaksi</p>
                    <p class="sale-view-total-value">{{ $this->formatCurrency($sale->total) }}</p>
                </div>
            </div>

            <div class="sale-view-stat-grid">
                <div class="sale-view-stat">
                    <p class="sale-view-stat-label">Jumlah Baris</p>
                    <p class="sale-view-stat-value">{{ number_format($this->getLineCount()) }}</p>
                </div>

                <div class="sale-view-stat">
                    <p class="sale-view-stat-label">Total Qty</p>
                    <p class="sale-view-stat-value">{{ number_format($this->getTotalQuantity()) }}</p>
                </div>

                <div class="sale-view-stat">
                    <p class="sale-view-stat-label">Baris Service</p>
                    <p class="sale-view-stat-value">{{ number_format($this->getServiceLineCount()) }}</p>
                </div>
            </div>
        </section>

        <div class="sale-view-grid">
            <div class="sale-view-card">
                <div class="sale-view-card-head">
                    <h3 class="sale-view-card-title">Detail Item</h3>
                    <span class="sale-view-badge sale-view-badge-muted">{{ number_format($items->count()) }} baris</span>
                </div>

                <div class="sale-view-card-body">
                    @if ($items->isEmpty())
                        <div class="sale-view-empty">Tidak ada item pada transaksi ini.</div>
                    @else
                        <div class="sale-view-table-wrap">
                            <table class="sale-view-table">
                                <thead>
                                    <tr>
                                        <th>Produk</th>
                                        <th>Tipe</th>
                                        <th>Pegawai</th>
                                        <th>Tier</th>
                                        <th style="text-align: right;">Qty</th>
                                        <th style="text-align: right;">Harga</th>
                                        <th style="text-align: right;">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($items as $item)
                                        @php
                                            $kind = $this->getItemKindLabel($item);
                                            $kindClass = match ($item->product?->product_type) {
                                                'service' => 'sale-view-badge-service',
                                                'retail' => 'sale-view-badge-retail',
                                                'consumable' => 'sale-view-badge-consumable',
                                                default => 'sale-view-badge-muted',
                                            };
                                        @endphp

                                        <tr>
                                            <td>
                                                <div class="sale-view-product">
                                                    <span class="sale-view-product-name">{{ $item->product?->product_name ?: '-' }}</span>

                                                    @if (filled($item->notes))
                                                        <span class="sale-view-product-note">{{ $item->notes }}</span>
                                                    @endif
                                                </div>
                                            </td>
                                            <td>
                                                <span class="sale-view-badge {{ $kindClass }}">{{ $kind }}</span>
                                            </td>
                                            <td>{{ $item->employee?->emp_name ?: '-' }}</td>
                                            <td>{{ $item->price_tier === 'callout' ? 'Panggilan' : 'Reguler' }}</td>
                                            <td style="text-align: right;">{{ number_format((float) $item->qty, 0, ',', '.') }}</td>
                                            <td style="text-align: right;">{{ $this->formatCurrency($item->unit_price) }}</td>
                                            <td style="text-align: right; font-weight: 700;">{{ $this->formatCurrency($item->line_total) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            <div class="sale-view-stack">
                <div class="sale-view-card">
                    <div class="sale-view-card-head">
                        <h3 class="sale-view-card-title">Informasi Invoice</h3>
                    </div>

                    <div class="sale-view-card-body sale-view-meta-grid">
                        <div class="sale-view-meta-row">
                            <div>
                                <p class="sale-view-meta-label">Invoice</p>
                                <p class="sale-view-meta-value">{{ $sale->invoice_no }}</p>
                            </div>
                        </div>

                        <div class="sale-view-divider"></div>

                        <div class="sale-view-meta-row">
                            <div>
                                <p class="sale-view-meta-label">Tanggal</p>
                                <p class="sale-view-meta-value">{{ $sale->paid_at?->format('d M Y, H:i:s') ?: '-' }}</p>
                            </div>
                        </div>

                        <div class="sale-view-meta-row">
                            <div>
                                <p class="sale-view-meta-label">Kasir</p>
                                <p class="sale-view-meta-value">{{ $sale->cashier?->emp_name ?: '-' }}</p>
                            </div>
                        </div>

                        <div class="sale-view-meta-row">
                            <div>
                                <p class="sale-view-meta-label">Metode Pembayaran</p>
                                <p class="sale-view-meta-value">{{ $sale->paymentMethod?->method_name ?: '-' }}</p>
                            </div>
                        </div>

                        <div class="sale-view-meta-row">
                            <div>
                                <p class="sale-view-meta-label">Input Oleh</p>
                                <p class="sale-view-meta-value">{{ $sale->creator?->name ?: '-' }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="sale-view-card">
                    <div class="sale-view-card-head">
                        <h3 class="sale-view-card-title">Customer</h3>
                    </div>

                    <div class="sale-view-card-body sale-view-meta-grid">
                        <div class="sale-view-meta-row">
                            <div>
                                <p class="sale-view-meta-label">Nama</p>
                                <p class="sale-view-meta-value">{{ $sale->customer_name ?: '-' }}</p>
                            </div>
                        </div>

                        <div class="sale-view-meta-row">
                            <div>
                                <p class="sale-view-meta-label">No HP</p>
                                <p class="sale-view-meta-value">{{ $sale->customer_phone ?: '-' }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="sale-view-card">
                    <div class="sale-view-card-head">
                        <h3 class="sale-view-card-title">Ringkasan Pembayaran</h3>
                    </div>

                    <div class="sale-view-card-body">
                        <div class="sale-view-summary-row">
                            <span>Subtotal</span>
                            <span>{{ $this->formatCurrency($sale->subtotal) }}</span>
                        </div>

                        <div class="sale-view-summary-row">
                            <span>Diskon</span>
                            <span>{{ $this->formatCurrency($sale->discount) }}</span>
                        </div>

                        <div class="sale-view-divider" style="margin: 0.35rem 0 0.55rem;"></div>

                        <div class="sale-view-summary-row">
                            <strong>Total</strong>
                            <strong>{{ $this->formatCurrency($sale->total) }}</strong>
                        </div>
                    </div>
                </div>

                <div class="sale-view-card">
                    <div class="sale-view-card-head">
                        <h3 class="sale-view-card-title">Catatan</h3>
                    </div>

                    <div class="sale-view-card-body">
                        @if (filled($sale->notes))
                            <div class="sale-view-product-note" style="font-size: 0.95rem; color: var(--sale-text-secondary);">
                                {{ $sale->notes }}
                            </div>
                        @else
                            <div class="sale-view-empty" style="padding: 1rem;">Tidak ada catatan untuk transaksi ini.</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
