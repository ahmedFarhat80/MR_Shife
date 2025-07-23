<?php

namespace App\Filament\Resources\CustomerResource\Pages;

use App\Filament\Resources\CustomerResource;
use App\Models\Customer;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewCustomer extends ViewRecord
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('تعديل'),
            Actions\Action::make('suspend')
                ->label('تعليق الحساب')
                ->icon('heroicon-o-pause')
                ->color('warning')
                ->requiresConfirmation()
                ->action(fn (Customer $record) => $record->update(['status' => 'suspended']))
                ->visible(fn (Customer $record) => $record->status === 'active'),
            Actions\Action::make('activate')
                ->label('تفعيل الحساب')
                ->icon('heroicon-o-play')
                ->color('success')
                ->action(fn (Customer $record) => $record->update(['status' => 'active']))
                ->visible(fn (Customer $record) => in_array($record->status, ['inactive', 'suspended'])),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('المعلومات الأساسية')
                    ->schema([
                        Infolists\Components\ImageEntry::make('avatar')
                            ->label('الصورة الشخصية')
                            ->circular()
                            ->defaultImageUrl(fn ($record) =>
                                'https://ui-avatars.com/api/?name=' . urlencode($record->name ?? 'عميل') . '&background=794E24&color=fff&size=200'
                            ),
                        Infolists\Components\TextEntry::make('name')
                            ->label('الاسم'),
                        Infolists\Components\TextEntry::make('phone_number')
                            ->label('رقم الهاتف')
                            ->icon('heroicon-m-phone')
                            ->copyable(),
                        Infolists\Components\TextEntry::make('email')
                            ->label('البريد الإلكتروني')
                            ->icon('heroicon-m-envelope')
                            ->copyable(),
                        Infolists\Components\TextEntry::make('preferred_language')
                            ->label('اللغة المفضلة')
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'ar' => 'العربية',
                                'en' => 'English',
                                default => $state,
                            }),
                        Infolists\Components\TextEntry::make('gender')
                            ->label('الجنس')
                            ->formatStateUsing(fn (?string $state): string => match ($state) {
                                'male' => 'ذكر',
                                'female' => 'أنثى',
                                default => 'غير محدد',
                            }),
                        Infolists\Components\TextEntry::make('date_of_birth')
                            ->label('تاريخ الميلاد')
                            ->date('d/m/Y'),
                    ])->columns(2),

                Infolists\Components\Section::make('حالة الحساب')
                    ->schema([
                        Infolists\Components\TextEntry::make('status')
                            ->label('حالة الحساب')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'active' => 'success',
                                'inactive' => 'gray',
                                'suspended' => 'warning',
                                'banned' => 'danger',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'active' => 'نشط',
                                'inactive' => 'غير نشط',
                                'suspended' => 'معلق',
                                'banned' => 'محظور',
                                default => $state,
                            }),
                        Infolists\Components\IconEntry::make('phone_verified')
                            ->label('تم التحقق من الهاتف')
                            ->boolean()
                            ->trueIcon('heroicon-o-check-circle')
                            ->falseIcon('heroicon-o-x-circle')
                            ->trueColor('success')
                            ->falseColor('danger'),
                        Infolists\Components\IconEntry::make('email_verified')
                            ->label('تم التحقق من البريد الإلكتروني')
                            ->boolean()
                            ->trueIcon('heroicon-o-check-circle')
                            ->falseIcon('heroicon-o-x-circle')
                            ->trueColor('success')
                            ->falseColor('danger'),
                        Infolists\Components\TextEntry::make('loyalty_points')
                            ->label('نقاط الولاء')
                            ->numeric()
                            ->badge()
                            ->color('warning'),
                    ])->columns(2),

                Infolists\Components\Section::make('إحصائيات العميل')
                    ->schema([
                        Infolists\Components\TextEntry::make('total_orders_count')
                            ->label('إجمالي الطلبات')
                            ->state(fn (Customer $record): int => $record->orders()->count())
                            ->badge()
                            ->color('info'),
                        Infolists\Components\TextEntry::make('completed_orders_count')
                            ->label('الطلبات المكتملة')
                            ->state(fn (Customer $record): int => $record->orders()->where('status', 'completed')->count())
                            ->badge()
                            ->color('success'),
                        Infolists\Components\TextEntry::make('total_spent')
                            ->label('إجمالي المبلغ المنفق')
                            ->state(fn (Customer $record): string => number_format($record->orders()->where('status', 'completed')->sum('total_amount'), 2) . ' ريال')
                            ->badge()
                            ->color('success'),
                        Infolists\Components\TextEntry::make('customer_tier')
                            ->label('مستوى العضوية')
                            ->state(fn (Customer $record): string => $record->customer_tier)
                            ->badge()
                            ->color(fn (Customer $record): string => match ($record->customer_tier) {
                                'platinum' => 'success',
                                'gold' => 'warning',
                                'silver' => 'info',
                                'bronze' => 'gray',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'platinum' => 'بلاتيني',
                                'gold' => 'ذهبي',
                                'silver' => 'فضي',
                                'bronze' => 'برونزي',
                                default => $state,
                            }),
                    ])->columns(2),

                Infolists\Components\Section::make('معلومات النظام')
                    ->schema([
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('تاريخ التسجيل')
                            ->dateTime('d/m/Y H:i'),
                        Infolists\Components\TextEntry::make('last_login_at')
                            ->label('آخر تسجيل دخول')
                            ->dateTime('d/m/Y H:i')
                            ->placeholder('لم يسجل دخول بعد'),
                        Infolists\Components\TextEntry::make('phone_verified_at')
                            ->label('تاريخ التحقق من الهاتف')
                            ->dateTime('d/m/Y H:i')
                            ->placeholder('لم يتم التحقق'),
                        Infolists\Components\TextEntry::make('email_verified_at')
                            ->label('تاريخ التحقق من البريد الإلكتروني')
                            ->dateTime('d/m/Y H:i')
                            ->placeholder('لم يتم التحقق'),
                    ])->columns(2),
            ]);
    }
}
