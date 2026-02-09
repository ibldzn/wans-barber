<x-filament-panels::page>
    {{ $this->form }}

    <x-filament::section heading="Ringkasan" style="margin-top: 1.5rem;">
        <div class="fi-prose">
            <table>
                <tbody>
                    <tr>
                        <td>Total Income</td>
                        <td style="text-align: right;">Rp {{ number_format($incomeTotal, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td>Total Expense</td>
                        <td style="text-align: right;">Rp {{ number_format($expenseTotal, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td>Net</td>
                        <td style="text-align: right;">Rp {{ number_format($netTotal, 0, ',', '.') }}</td>
                    </tr>
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
