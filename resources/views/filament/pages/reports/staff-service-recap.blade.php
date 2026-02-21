<x-filament-panels::page>
    {{ $this->form }}

    @if (! $employee)
        <x-filament::section heading="Rekap" style="margin-top: 1.5rem;">
            <div class="fi-prose">Pilih petugas terlebih dahulu.</div>
        </x-filament::section>
    @else
        <x-filament::section heading="Informasi" style="margin-top: 1.5rem;">
            <div class="fi-prose">
                <div><strong>Petugas:</strong> {{ $employee->emp_name }}</div>
                <div><strong>Periode:</strong> {{ $start->format('Y-m-d') }} s.d. {{ $end->format('Y-m-d') }}</div>
            </div>
        </x-filament::section>

        <x-filament::section heading="Detail Service" style="margin-top: 1.5rem;">
            <div class="fi-prose">
                <table>
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th style="text-align: right;">Qty</th>
                            <th style="text-align: right;">Harga</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $row)
                            <tr>
                                <td>{{ $row['product']->product_name }}</td>
                                <td style="text-align: right;">{{ number_format($row['qty']) }}</td>
                                <td style="text-align: right;">Rp {{ number_format($row['total'], 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3">Tidak ada data.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>

        <x-filament::section heading="Summary" style="margin-top: 2rem;">
            <div class="fi-prose">
                @forelse ($summaries as $category => $summary)
                    <h4 style="margin-bottom: 0.5rem;">{{ $category }}</h4>
                    <table style="margin-bottom: 1.25rem;">
                        <tbody>
                            <tr>
                                <td>Total Omzet</td>
                                <td style="text-align: right;">Rp {{ number_format($summary['omzet'], 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td>Komisi Petugas</td>
                                <td style="text-align: right;">
                                    {{ number_format($summary['petugas_rate'] * 100, 2, ',', '.') }}%
                                    | Rp {{ number_format($summary['petugas_commission'], 0, ',', '.') }}
                                </td>
                            </tr>
                            <tr>
                                <td>Komisi Barber</td>
                                <td style="text-align: right;">
                                    {{ number_format($summary['barber_rate'] * 100, 2, ',', '.') }}%
                                    | Rp {{ number_format($summary['barber_commission'], 0, ',', '.') }}
                                </td>
                            </tr>
                            <tr>
                                <td>Estimasi Biaya Consumable</td>
                                <td style="text-align: right;">Rp {{ number_format($summary['consumable_cost'], 0, ',', '.') }}</td>
                            </tr>
                        </tbody>
                    </table>
                @empty
                    <div>Tidak ada ringkasan.</div>
                @endforelse
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>
