{{-- Charts Row - صف الرسوم البيانية --}}
<div class="w-full fi-wi-chart" style="grid-column: 1 / -1;">
    <div class="flex w-full">

        {{-- Col-6: مخططات الأداء الشهرية --}}
        <div class="w-full mb-6">
            @livewire(\App\Filament\Widgets\PerformanceChartsWidget::class)
        </div>

        {{-- <div class="w-1/2 mb-6">
            <div class="ps-6">
                <div class="h-[850px]">
                    @livewire(\App\Filament\Widgets\MerchantRegistrationsChart::class)
                </div>
            </div>
        </div> --}}

    </div>
</div>
