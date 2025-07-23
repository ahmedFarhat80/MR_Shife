<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex items-center gap-x-3">
            <x-filament-panels::avatar.user size="lg" :user="$user" />

            <div class="flex-1">
                <h2 class="grid flex-1 text-base font-semibold leading-6 text-gray-950 dark:text-white">
                    {{ $welcomeMessage }}
                </h2>

                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ config('app.name') }} {{ __('admin.title') }}
                </p>
            </div>

            <div class="flex flex-col items-end gap-y-1">
                <x-filament::link color="gray" icon="heroicon-m-arrow-right-start-on-rectangle"
                    icon-alias="panels::widgets.account.sign-out-button" labeled-from="sm" tag="button"
                    wire:click="$dispatch('open-modal', { id: 'logout' })">
                    {{ $signOutLabel }}
                </x-filament::link>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
