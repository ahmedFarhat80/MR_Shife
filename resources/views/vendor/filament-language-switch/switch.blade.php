{{-- Language Switch as Menu Item --}}
<div class="fi-dropdown-list-item">
    <x-filament::dropdown
        teleport
        :placement="$placement"
        :width="$isFlagsOnly ? 'flags-only' : 'fls-dropdown-width'"
    >
        <x-slot name="trigger">
            <button
                type="button"
                class="flex items-center w-full gap-3 px-3 py-2 text-sm font-medium transition-colors duration-75 rounded-lg outline-none fi-dropdown-list-item hover:bg-gray-50 focus:bg-gray-50 dark:hover:bg-white/5 dark:focus:bg-white/5"
            >
                @if ($isFlagsOnly || $hasFlags)
                    <x-filament-language-switch::flag
                        :src="$languageSwitch->getFlag(app()->getLocale())"
                        :circular="$isCircular"
                        :alt="$languageSwitch->getLabel(app()->getLocale())"
                        class="w-5 h-5"
                    />
                @else
                    <span class="flex items-center justify-center w-5 h-5 text-xs font-semibold rounded-full bg-primary-500/10 text-primary-600">
                        {{ $languageSwitch->getCharAvatar(app()->getLocale()) }}
                    </span>
                @endif

                <span class="text-gray-700 dark:text-gray-200">
                    {{ $languageSwitch->getLabel(app()->getLocale()) }}
                </span>

                <x-heroicon-m-chevron-down class="w-4 h-4 ml-auto text-gray-400" />
            </button>
        </x-slot>

    <x-filament::dropdown.list @class(['!border-t-0 space-y-1 !p-2.5'])>
        @foreach ($locales as $locale)
            @if (!app()->isLocale($locale))
                <button
                    type="button"
                    wire:click="changeLocale('{{ $locale }}')"
                    @if ($isFlagsOnly)
                    x-tooltip="{
                        content: @js($languageSwitch->getLabel($locale)),
                        theme: $store.theme,
                        placement: 'right'
                    }"
                    @endif

                    @class([
                        'flex items-center w-full transition-colors duration-75 rounded-md outline-none fi-dropdown-list-item whitespace-nowrap disabled:pointer-events-none disabled:opacity-70 fi-dropdown-list-item-color-gray hover:bg-gray-950/5 focus:bg-gray-950/5 dark:hover:bg-white/5 dark:focus:bg-white/5',
                        'justify-center px-2 py-0.5' => $isFlagsOnly,
                        'justify-start space-x-2 rtl:space-x-reverse p-1' => !$isFlagsOnly,
                    ])
                >

                    @if ($isFlagsOnly)
                        <x-filament-language-switch::flag
                            :src="$languageSwitch->getFlag($locale)"
                            :circular="$isCircular"
                            :alt="$languageSwitch->getLabel($locale)"
                            class="w-7 h-7"
                        />
                    @else
                        @if ($hasFlags)
                            <x-filament-language-switch::flag
                                :src="$languageSwitch->getFlag($locale)"
                                :circular="$isCircular"
                                :alt="$languageSwitch->getLabel($locale)"
                                class="w-7 h-7"
                            />
                        @else
                            <span
                                @class([
                                    'flex items-center justify-center flex-shrink-0 w-7 h-7 p-2 text-xs font-semibold group-hover:bg-white group-hover:text-primary-600 group-hover:border group-hover:border-primary-500/10 group-focus:text-white bg-primary-500/10 text-primary-600',
                                    'rounded-full' => $isCircular,
                                    'rounded-lg' => !$isCircular,
                                ])
                            >
                                {{ $languageSwitch->getCharAvatar($locale) }}
                            </span>
                        @endif
                        <span class="text-sm font-medium text-gray-600 hover:bg-transparent dark:text-gray-200">
                            {{ $languageSwitch->getLabel($locale) }}
                        </span>

                    @endif
                </button>
            @endif
        @endforeach
    </x-filament::dropdown.list>
    </x-filament::dropdown>
</div>
