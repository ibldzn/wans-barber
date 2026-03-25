<x-filament-panels::page>
    {{ $this->form }}

    <div class="grid gap-4 md:grid-cols-3" style="margin-top: 1.5rem;">
        <x-filament::section heading="Gross Income">
            <p class="text-2xl font-semibold">
                Rp {{ number_format($grossIncome, 0, ',', '.') }}
            </p>
        </x-filament::section>

        <x-filament::section heading="Total Expense">
            <p class="text-2xl font-semibold">
                Rp {{ number_format($totalExpense, 0, ',', '.') }}
            </p>
        </x-filament::section>

        <x-filament::section heading="Net Profit">
            <p class="text-2xl font-semibold">
                Rp {{ number_format($netProfit, 0, ',', '.') }}
            </p>
        </x-filament::section>
    </div>

    <x-filament::section heading="Rekap Harian" style="margin-top: 2rem;">
        <div class="fi-prose">
            <table>
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th style="text-align: right;">Income</th>
                        <th style="text-align: right;">Expense</th>
                        <th style="text-align: right;">Net</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($dailyRows as $row)
                        <tr>
                            <td>{{ \Illuminate\Support\Carbon::parse($row['date'])->format('d/m/Y') }}</td>
                            <td style="text-align: right;">Rp {{ number_format($row['income'], 0, ',', '.') }}</td>
                            <td style="text-align: right;">Rp {{ number_format($row['expense'], 0, ',', '.') }}</td>
                            <td style="text-align: right;">Rp {{ number_format($row['net'], 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">Tidak ada data.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>

    <x-filament::section heading="Income by Category" style="margin-top: 2rem;">
        <div class="fi-prose">
            <table>
                <thead>
                    <tr>
                        <th>Kategori</th>
                        <th style="text-align: right;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($incomeByCategory as $name => $total)
                        <tr>
                            <td>{{ $name }}</td>
                            <td style="text-align: right;">Rp {{ number_format($total, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2">Tidak ada data.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>

    <x-filament::section heading="Expense by Category" style="margin-top: 2rem;">
        <div class="fi-prose">
            <table>
                <thead>
                    <tr>
                        <th>Kategori</th>
                        <th style="text-align: right;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($expenseByCategory as $name => $total)
                        <tr>
                            <td>{{ $name }}</td>
                            <td style="text-align: right;">Rp {{ number_format($total, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2">Tidak ada data.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament-panels::page>
