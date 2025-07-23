<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class ChartsRowWidget extends Widget
{
    protected static string $view = 'filament.widgets.charts-row-widget';

    // تأكد من أن الـ widget يأخذ العرض الكامل
    protected int | string | array $columnSpan = 'full';

    // إزالة أي قيود على العرض
    protected static bool $isLazy = false;

    protected static ?int $sort = 10;
}
