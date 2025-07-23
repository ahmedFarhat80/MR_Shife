<?php

namespace App\Filament\Resources\MerchantResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class OrdersRelationManager extends RelationManager
{
    protected static string $relationship = 'orders';

    protected static ?string $title = 'طلبات التاجر';

    protected static ?string $modelLabel = 'طلب';

    protected static ?string $pluralModelLabel = 'الطلبات';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات الطلب')
                    ->schema([
                        Forms\Components\TextInput::make('order_number')
                            ->label('رقم الطلب')
                            ->required()
                            ->maxLength(191)
                            ->disabled(),

                        Forms\Components\Select::make('customer_id')
                            ->label('العميل')
                            ->relationship('customer', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),

                        Forms\Components\Select::make('status')
                            ->label('حالة الطلب')
                            ->required()
                            ->options([
                                'pending' => 'في الانتظار',
                                'confirmed' => 'مؤكد',
                                'preparing' => 'قيد التحضير',
                                'ready' => 'جاهز',
                                'out_for_delivery' => 'في الطريق',
                                'delivered' => 'تم التسليم',
                                'cancelled' => 'ملغي',
                                'rejected' => 'مرفوض',
                            ])
                            ->native(false),

                        Forms\Components\Select::make('payment_status')
                            ->label('حالة الدفع')
                            ->required()
                            ->options([
                                'pending' => 'في الانتظار',
                                'paid' => 'مدفوع',
                                'failed' => 'فشل',
                                'refunded' => 'مسترد',
                            ])
                            ->native(false),

                        Forms\Components\Select::make('payment_method')
                            ->label('طريقة الدفع')
                            ->required()
                            ->options([
                                'cash' => 'نقدي',
                                'card' => 'بطاقة ائتمان',
                                'wallet' => 'محفظة إلكترونية',
                                'bank_transfer' => 'تحويل بنكي',
                            ])
                            ->native(false),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('تفاصيل المبالغ')
                    ->schema([
                        Forms\Components\TextInput::make('subtotal')
                            ->label('المجموع الفرعي')
                            ->required()
                            ->numeric()
                            ->prefix('ر.س'),

                        Forms\Components\TextInput::make('tax_amount')
                            ->label('الضريبة')
                            ->required()
                            ->numeric()
                            ->default(0.00)
                            ->prefix('ر.س'),

                        Forms\Components\TextInput::make('delivery_fee')
                            ->label('رسوم التوصيل')
                            ->required()
                            ->numeric()
                            ->default(0.00)
                            ->prefix('ر.س'),

                        Forms\Components\TextInput::make('service_fee')
                            ->label('رسوم الخدمة')
                            ->required()
                            ->numeric()
                            ->default(0.00)
                            ->prefix('ر.س'),

                        Forms\Components\TextInput::make('discount_amount')
                            ->label('مبلغ الخصم')
                            ->required()
                            ->numeric()
                            ->default(0.00)
                            ->prefix('ر.س'),

                        Forms\Components\TextInput::make('total_amount')
                            ->label('المبلغ الإجمالي')
                            ->required()
                            ->numeric()
                            ->prefix('ر.س'),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('معلومات التوصيل والملاحظات')
                    ->schema([
                        Forms\Components\Textarea::make('delivery_address')
                            ->label('عنوان التوصيل')
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('notes')
                            ->label('ملاحظات')
                            ->rows(2)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('rejection_reason')
                            ->label('سبب الرفض')
                            ->rows(2)
                            ->columnSpanFull()
                            ->visible(fn (Forms\Get $get) => $get('status') === 'rejected'),
                    ]),

                Forms\Components\Section::make('التوقيتات')
                    ->schema([
                        Forms\Components\DateTimePicker::make('estimated_delivery_time')
                            ->label('الوقت المتوقع للتسليم'),

                        Forms\Components\DateTimePicker::make('confirmed_at')
                            ->label('وقت التأكيد'),

                        Forms\Components\DateTimePicker::make('prepared_at')
                            ->label('وقت التحضير'),

                        Forms\Components\DateTimePicker::make('delivered_at')
                            ->label('وقت التسليم'),
                    ])
                    ->columns(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('order_number')
            ->columns([
                Tables\Columns\TextColumn::make('order_number')
                    ->label('رقم الطلب')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('customer.name')
                    ->label('العميل')
                    ->searchable()
                    ->sortable()
                    ->limit(20),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('حالة الطلب')
                    ->colors([
                        'warning' => 'pending',
                        'info' => 'confirmed',
                        'primary' => 'preparing',
                        'success' => ['ready', 'delivered'],
                        'danger' => ['cancelled', 'rejected'],
                        'secondary' => 'out_for_delivery',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'في الانتظار',
                        'confirmed' => 'مؤكد',
                        'preparing' => 'قيد التحضير',
                        'ready' => 'جاهز',
                        'out_for_delivery' => 'في الطريق',
                        'delivered' => 'تم التسليم',
                        'cancelled' => 'ملغي',
                        'rejected' => 'مرفوض',
                        default => $state,
                    }),

                Tables\Columns\BadgeColumn::make('payment_status')
                    ->label('حالة الدفع')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'paid',
                        'danger' => 'failed',
                        'info' => 'refunded',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'في الانتظار',
                        'paid' => 'مدفوع',
                        'failed' => 'فشل',
                        'refunded' => 'مسترد',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('payment_method')
                    ->label('طريقة الدفع')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'cash' => 'نقدي',
                        'card' => 'بطاقة ائتمان',
                        'wallet' => 'محفظة إلكترونية',
                        'bank_transfer' => 'تحويل بنكي',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label('المبلغ الإجمالي')
                    ->money('SAR')
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('estimated_delivery_time')
                    ->label('الوقت المتوقع')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الطلب')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->since(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('حالة الطلب')
                    ->options([
                        'pending' => 'في الانتظار',
                        'confirmed' => 'مؤكد',
                        'preparing' => 'قيد التحضير',
                        'ready' => 'جاهز',
                        'out_for_delivery' => 'في الطريق',
                        'delivered' => 'تم التسليم',
                        'cancelled' => 'ملغي',
                        'rejected' => 'مرفوض',
                    ]),

                Tables\Filters\SelectFilter::make('payment_status')
                    ->label('حالة الدفع')
                    ->options([
                        'pending' => 'في الانتظار',
                        'paid' => 'مدفوع',
                        'failed' => 'فشل',
                        'refunded' => 'مسترد',
                    ]),

                Tables\Filters\Filter::make('created_at')
                    ->label('تاريخ الطلب')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('من تاريخ'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('إلى تاريخ'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('إضافة طلب جديد')
                    ->icon('heroicon-o-plus'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('عرض'),
                Tables\Actions\EditAction::make()
                    ->label('تعديل'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('حذف المحدد'),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('لا توجد طلبات')
            ->emptyStateDescription('لم يتم تسجيل أي طلبات لهذا التاجر بعد.')
            ->emptyStateIcon('heroicon-o-shopping-cart');
    }
}
