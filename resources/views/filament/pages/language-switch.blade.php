<x-filament-panels::page>
    <div class="space-y-6">
        <div class="text-center">
            <h2 class="text-2xl font-bold">{{ __('Language Settings') }}</h2>
            <p class="text-gray-600 mt-2">{{ __('Choose your preferred language') }}</p>
        </div>
        
        <div class="grid grid-cols-2 gap-4">
            <div class="p-6 border rounded-lg text-center hover:bg-gray-50 cursor-pointer" 
                 wire:click="switchToArabic">
                <div class="text-4xl mb-4">🇸🇦</div>
                <h3 class="text-lg font-semibold">العربية</h3>
                <p class="text-gray-600">Arabic</p>
            </div>
            
            <div class="p-6 border rounded-lg text-center hover:bg-gray-50 cursor-pointer"
                 wire:click="switchToEnglish">
                <div class="text-4xl mb-4">🇺🇸</div>
                <h3 class="text-lg font-semibold">English</h3>
                <p class="text-gray-600">الإنجليزية</p>
            </div>
        </div>
    </div>
</x-filament-panels::page>
