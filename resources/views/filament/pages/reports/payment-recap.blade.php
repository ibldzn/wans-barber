<x-filament-panels::page>
    {{ $this->form }}

    <x-filament::section heading="Total per Metode" style="margin-top: 1.5rem;">
        <div class="fi-prose">
            <table>
                <thead>
                    <tr>
                        <th>Metode</th>
                        <th style="text-align: right;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($totalsByMethod as $method => $total)
                        <tr>
                            <td>{{ $method }}</td>
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

    <x-filament::section heading="Detail per Tanggal" style="margin-top: 2rem;">
        <div class="fi-prose">
            <table>
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Metode</th>
                        <th style="text-align: right;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($byDate as $date => $methods)
                        @foreach ($methods as $method => $total)
                            <tr>
                                <td>{{ $date }}</td>
                                <td>{{ $method }}</td>
                                <td style="text-align: right;">Rp {{ number_format($total, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    @empty
                        <tr>
                            <td colspan="3">Tidak ada data.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament-panels::page>
