<?php

namespace App\Filament\Widgets;

use Filament\Widgets\AccountWidget as BaseAccountWidget;

class AccountWidget extends BaseAccountWidget
{
    protected static ?int $sort = -3;

    protected static bool $isLazy = false;

    protected function getViewData(): array
    {
        return [
            'user' => auth()->user(),
            'welcomeMessage' => __('dashboard.welcome'),
            'signOutLabel' => __('dashboard.sign_out'),
        ];
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('filament.widgets.account-widget', $this->getViewData());
    }
}
