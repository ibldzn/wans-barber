<x-filament-panels::page>
    @if ($items->isEmpty())
        <x-filament::section heading="Dokumentasi">
            <div class="fi-prose">
                Belum ada item dokumentasi yang ditampilkan.
            </div>
        </x-filament::section>
    @else
        <div class="grid gap-8 md:gap-10 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($items as $item)
                <x-filament::section>
                    <div class="space-y-4">
                        <div class="space-y-1">
                            <div class="text-base font-semibold text-gray-950 dark:text-white">
                                {{ $item->label }}
                            </div>

                            @if (filled($item->description))
                                <div class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ $item->description }}
                                </div>
                            @endif
                        </div>

                        @php
                            $formattedValue = $item->getFormattedValue();
                            $plainValue = $item->getPlainValue();
                            $href = $item->getLinkHref();
                        @endphp

                        @if ($item->field_type === \App\Models\DocumentationItem::FIELD_TYPE_SECRET)
                            @if (blank($plainValue))
                                <div class="text-sm text-gray-500 dark:text-gray-400">-</div>
                            @elseif ($canRevealSecrets)
                                <div
                                    x-data="{ revealed: false, copied: false, value: @js($plainValue) }"
                                    class="space-y-3"
                                >
                                    <div class="font-mono text-sm text-gray-950 dark:text-white">
                                        <span x-show="! revealed">{{ $item->getMaskedValue() }}</span>
                                        <span x-show="revealed" x-text="value"></span>
                                    </div>

                                    <div class="flex items-center gap-2">
                                        <x-filament::button
                                            color="gray"
                                            size="xs"
                                            type="button"
                                            x-on:click="revealed = ! revealed"
                                        >
                                            <span x-show="! revealed">Tampilkan</span>
                                            <span x-show="revealed">Sembunyikan</span>
                                        </x-filament::button>

                                        <x-filament::button
                                            color="gray"
                                            size="xs"
                                            type="button"
                                            x-on:click="navigator.clipboard.writeText(value); copied = true; setTimeout(() => copied = false, 1500)"
                                        >
                                            Copy
                                        </x-filament::button>

                                        <span
                                            x-show="copied"
                                            class="text-xs text-success-600 dark:text-success-400"
                                        >
                                            Disalin
                                        </span>
                                    </div>
                                </div>
                            @else
                                <div class="font-mono text-sm text-gray-950 dark:text-white">
                                    {{ $item->getMaskedValue() }}
                                </div>
                            @endif
                        @elseif (blank($formattedValue))
                            <div class="text-sm text-gray-500 dark:text-gray-400">-</div>
                        @elseif ($item->field_type === \App\Models\DocumentationItem::FIELD_TYPE_MULTILINE)
                            <div class="whitespace-pre-line text-sm leading-6 text-gray-950 dark:text-white">
                                {{ $formattedValue }}
                            </div>
                        @elseif ($href)
                            <a
                                href="{{ $href }}"
                                @if ($item->field_type === \App\Models\DocumentationItem::FIELD_TYPE_URL) target="_blank" rel="noreferrer noopener" @endif
                                class="text-sm font-medium text-primary-600 hover:underline dark:text-primary-400"
                            >
                                {{ $formattedValue }}
                            </a>
                        @else
                            <div class="text-sm leading-6 text-gray-950 dark:text-white">
                                {{ $formattedValue }}
                            </div>
                        @endif
                    </div>
                </x-filament::section>
            @endforeach
        </div>
    @endif
</x-filament-panels::page>
