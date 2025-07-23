<?php

namespace App\Filament\Resources\MerchantResource\Pages;

use App\Filament\Resources\MerchantResource;
use App\Models\Merchant;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\IconEntry;

class ViewMerchant extends ViewRecord
{
    protected static string $resource = MerchantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('تعديل'),
            Actions\Action::make('approve')
                ->label('موافقة')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->action(fn (Merchant $record) => $record->update(['is_approved' => true]))
                ->visible(fn (Merchant $record) => !$record->is_approved),
            Actions\Action::make('suspend')
                ->label('تعليق')
                ->icon('heroicon-o-pause')
                ->color('warning')
                ->requiresConfirmation()
                ->action(fn (Merchant $record) => $record->update(['is_approved' => false]))
                ->visible(fn (Merchant $record) => $record->is_approved),
            Actions\Action::make('verify')
                ->label('تحقق')
                ->icon('heroicon-o-shield-check')
                ->color('info')
                ->requiresConfirmation()
                ->action(fn (Merchant $record) => $record->update(['is_verified' => true]))
                ->visible(fn (Merchant $record) => !$record->is_verified),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('المعلومات الأساسية')
                    ->schema([
                        TextEntry::make('name')
                            ->label('الاسم')
                            ->formatStateUsing(fn ($state) => is_array($state) ? ($state['ar'] ?? $state['en'] ?? 'غير محدد') : $state),
                        TextEntry::make('phone_number')
                            ->label('رقم الهاتف'),
                        TextEntry::make('email')
                            ->label('البريد الإلكتروني'),
                        TextEntry::make('preferred_language')
                            ->label('اللغة المفضلة')
                            ->formatStateUsing(fn ($state) => $state === 'ar' ? 'العربية' : 'English'),
                    ])->columns(2),

                Section::make('معلومات العمل')
                    ->schema([
                        TextEntry::make('business_name')
                            ->label('اسم العمل'),
                        TextEntry::make('business_type')
                            ->label('نوع العمل')
                            ->formatStateUsing(fn ($state) => match($state) {
                                'restaurant' => 'مطعم',
                                'cafe' => 'مقهى',
                                'bakery' => 'مخبز',
                                'grocery' => 'بقالة',
                                default => $state
                            }),
                        TextEntry::make('business_description')
                            ->label('وصف العمل')
                            ->columnSpanFull(),
                        TextEntry::make('business_address')
                            ->label('عنوان العمل')
                            ->formatStateUsing(function ($state) {
                                if (is_array($state)) {
                                    return implode(', ', array_filter([
                                        $state['street'] ?? '',
                                        $state['city'] ?? '',
                                        $state['state'] ?? '',
                                        $state['country'] ?? ''
                                    ]));
                                }
                                return $state;
                            })
                            ->columnSpanFull(),
                    ])->columns(2),

                Section::make('الحالة والتحقق')
                    ->schema([
                        IconEntry::make('is_approved')
                            ->label('تمت الموافقة')
                            ->boolean(),
                        IconEntry::make('is_verified')
                            ->label('تم التحقق')
                            ->boolean(),
                        IconEntry::make('is_phone_verified')
                            ->label('تم التحقق من الهاتف')
                            ->boolean(),
                        TextEntry::make('phone_verified_at')
                            ->label('تاريخ التحقق من الهاتف')
                            ->dateTime('d/m/Y H:i'),
                    ])->columns(2),

                Section::make('الاشتراك')
                    ->schema([
                        TextEntry::make('subscription_plan')
                            ->label('خطة الاشتراك')
                            ->formatStateUsing(fn ($state) => $state ?? 'لا يوجد اشتراك'),
                        TextEntry::make('subscription_start_date')
                            ->label('تاريخ بداية الاشتراك')
                            ->date('d/m/Y'),
                        TextEntry::make('subscription_end_date')
                            ->label('تاريخ انتهاء الاشتراك')
                            ->date('d/m/Y'),
                    ])->columns(3),

                Section::make('الإحصائيات')
                    ->schema([
                        TextEntry::make('products_count')
                            ->label('عدد المنتجات')
                            ->state(fn ($record) => $record->products()->count()),
                        TextEntry::make('orders_count')
                            ->label('عدد الطلبات')
                            ->state(fn ($record) => $record->orders()->count()),
                        TextEntry::make('total_revenue')
                            ->label('إجمالي الإيرادات')
                            ->state(fn ($record) => number_format($record->orders()->sum('total_amount'), 2) . ' ريال'),
                    ])->columns(3),

                Section::make('التواريخ')
                    ->schema([
                        TextEntry::make('created_at')
                            ->label('تاريخ التسجيل')
                            ->dateTime('d/m/Y H:i'),
                        TextEntry::make('updated_at')
                            ->label('آخر تحديث')
                            ->dateTime('d/m/Y H:i'),
                    ])->columns(2),
            ]);
    }
}
