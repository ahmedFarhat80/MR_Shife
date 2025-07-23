<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use App\Models\Product;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\IconEntry;

class ViewProduct extends ViewRecord
{
    protected static string $resource = ProductResource::class;

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
                ->action(fn (Product $record) => $record->update(['is_approved' => true]))
                ->visible(fn (Product $record) => !$record->is_approved),
            Actions\Action::make('feature')
                ->label('تمييز')
                ->icon('heroicon-o-star')
                ->color('warning')
                ->requiresConfirmation()
                ->action(fn (Product $record) => $record->update(['is_featured' => !$record->is_featured]))
                ->label(fn (Product $record) => $record->is_featured ? 'إلغاء التمييز' : 'تمييز'),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('المعلومات الأساسية')
                    ->schema([
                        TextEntry::make('name')
                            ->label('اسم المنتج')
                            ->formatStateUsing(fn ($state) => is_array($state) ? ($state['ar'] ?? $state['en'] ?? 'غير محدد') : $state),
                        TextEntry::make('merchant.business_name')
                            ->label('التاجر'),
                        TextEntry::make('category.name')
                            ->label('الفئة الرئيسية')
                            ->formatStateUsing(fn ($state) => is_array($state) ? ($state['ar'] ?? $state['en'] ?? 'غير محدد') : $state),
                        TextEntry::make('internal_category.name')
                            ->label('الفئة الداخلية')
                            ->formatStateUsing(fn ($state) => is_array($state) ? ($state['ar'] ?? $state['en'] ?? 'غير محدد') : $state),
                    ])->columns(2),

                Section::make('الوصف والتفاصيل')
                    ->schema([
                        TextEntry::make('description')
                            ->label('الوصف')
                            ->formatStateUsing(fn ($state) => is_array($state) ? ($state['ar'] ?? $state['en'] ?? 'غير محدد') : $state)
                            ->columnSpanFull(),
                        TextEntry::make('ingredients')
                            ->label('المكونات')
                            ->formatStateUsing(fn ($state) => is_array($state) ? ($state['ar'] ?? $state['en'] ?? 'غير محدد') : $state)
                            ->columnSpanFull(),
                    ]),

                Section::make('السعر والتوفر')
                    ->schema([
                        TextEntry::make('price')
                            ->label('السعر')
                            ->formatStateUsing(fn ($state) => number_format($state, 2) . ' ريال'),
                        TextEntry::make('discount_price')
                            ->label('سعر الخصم')
                            ->formatStateUsing(fn ($state) => $state ? number_format($state, 2) . ' ريال' : 'لا يوجد خصم'),
                        IconEntry::make('is_available')
                            ->label('متوفر')
                            ->boolean(),
                        TextEntry::make('preparation_time')
                            ->label('وقت التحضير')
                            ->formatStateUsing(fn ($state) => $state ? $state . ' دقيقة' : 'غير محدد'),
                    ])->columns(2),

                Section::make('الحالة والتصنيف')
                    ->schema([
                        IconEntry::make('is_approved')
                            ->label('تمت الموافقة')
                            ->boolean(),
                        IconEntry::make('is_featured')
                            ->label('مميز')
                            ->boolean(),
                        TextEntry::make('food_nationality.name')
                            ->label('جنسية الطعام')
                            ->formatStateUsing(fn ($state) => is_array($state) ? ($state['ar'] ?? $state['en'] ?? 'غير محدد') : $state),
                        TextEntry::make('dietary_preferences')
                            ->label('التفضيلات الغذائية')
                            ->formatStateUsing(function ($state) {
                                if (is_array($state)) {
                                    $preferences = [
                                        'vegetarian' => 'نباتي',
                                        'vegan' => 'نباتي صرف',
                                        'gluten_free' => 'خالي من الجلوتين',
                                        'dairy_free' => 'خالي من الألبان',
                                        'halal' => 'حلال',
                                    ];
                                    return collect($state)->map(fn($pref) => $preferences[$pref] ?? $pref)->join(', ');
                                }
                                return $state ?? 'غير محدد';
                            }),
                    ])->columns(2),

                Section::make('الصور')
                    ->schema([
                        ImageEntry::make('images')
                            ->label('صور المنتج')
                            ->columnSpanFull()
                            ->limit(5)
                            ->ring(2),
                    ])
                    ->visible(fn ($record) => $record && !empty($record->images)),

                Section::make('الإحصائيات')
                    ->schema([
                        TextEntry::make('orders_count')
                            ->label('عدد الطلبات')
                            ->state(fn ($record) => $record->orderItems()->count()),
                        TextEntry::make('total_revenue')
                            ->label('إجمالي الإيرادات')
                            ->state(function ($record) {
                                $total = $record->orderItems()->sum('total_price');
                                return number_format($total, 2) . ' ريال';
                            }),
                        TextEntry::make('average_rating')
                            ->label('متوسط التقييم')
                            ->state(fn ($record) => $record->reviews()->avg('rating') ? number_format($record->reviews()->avg('rating'), 1) . '/5' : 'لا يوجد تقييم'),
                    ])->columns(3),

                Section::make('التواريخ')
                    ->schema([
                        TextEntry::make('created_at')
                            ->label('تاريخ الإنشاء')
                            ->dateTime('d/m/Y H:i'),
                        TextEntry::make('updated_at')
                            ->label('آخر تحديث')
                            ->dateTime('d/m/Y H:i'),
                    ])->columns(2),
            ]);
    }
}
