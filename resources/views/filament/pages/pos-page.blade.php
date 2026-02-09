<x-filament-panels::page>
    <form wire:submit.prevent="submit" class="fi-sc-form">
        {{ $this->form }}

        <x-filament::button type="submit" class="fi-ac fi-btn fi-ac-btn-action">
            Simpan Transaksi
        </x-filament::button>
    </form>
</x-filament-panels::page>
