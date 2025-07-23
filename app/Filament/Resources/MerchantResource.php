<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MerchantResource\Pages;
use App\Filament\Resources\MerchantResource\RelationManagers;
use App\Models\Merchant;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

class MerchantResource extends Resource
{
    protected static ?string $model = Merchant::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?string $navigationGroup = null;

    protected static ?string $navigationLabel = null;

    protected static ?string $modelLabel = null;

    protected static ?string $pluralModelLabel = null;

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.user_management');
    }

    public static function getNavigationLabel(): string
    {
        return __('merchant.title');
    }

    public static function getModelLabel(): string
    {
        return __('merchant.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('merchant.title');
    }

    protected static ?int $navigationSort = 4;

    protected static ?string $recordTitleAttribute = 'business_name';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Tabs')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('المعلومات الأساسية')
                            ->icon('heroicon-o-user')
                            ->schema([
                                Forms\Components\Section::make('بيانات الاتصال')
                                    ->schema([
                                        Forms\Components\TextInput::make('name')
                                            ->label('الاسم')
                                            ->required()
                                            ->maxLength(255)
                                            ->minLength(2)
                                            ->rules(['required', 'string', 'min:2', 'max:255'])
                                            ->validationMessages([
                                                'required' => 'الاسم مطلوب',
                                                'min' => 'الاسم يجب أن يكون على الأقل حرفين',
                                                'max' => 'الاسم لا يمكن أن يتجاوز 255 حرف',
                                            ]),
                                        Forms\Components\TextInput::make('phone_number')
                                            ->label('رقم الهاتف')
                                            ->tel()
                                            ->required()
                                            ->unique(ignoreRecord: true)
                                            ->maxLength(191)
                                            ->rules(['required', 'string', 'max:191', 'unique:merchants,phone_number'])
                                            ->validationMessages([
                                                'required' => 'رقم الهاتف مطلوب',
                                                'unique' => 'رقم الهاتف مستخدم بالفعل',
                                                'max' => 'رقم الهاتف لا يمكن أن يتجاوز 191 حرف',
                                            ]),
                                        Forms\Components\TextInput::make('email')
                                            ->label('البريد الإلكتروني')
                                            ->email()
                                            ->unique(ignoreRecord: true)
                                            ->maxLength(191)
                                            ->rules(['nullable', 'email', 'max:191', 'unique:merchants,email'])
                                            ->validationMessages([
                                                'email' => 'يرجى إدخال بريد إلكتروني صحيح',
                                                'unique' => 'هذا البريد الإلكتروني مستخدم بالفعل',
                                                'max' => 'البريد الإلكتروني لا يمكن أن يتجاوز 191 حرف',
                                            ]),
                                        Forms\Components\Select::make('preferred_language')
                                            ->label('اللغة المفضلة')
                                            ->options([
                                                'ar' => 'العربية',
                                                'en' => 'English',
                                            ])
                                            ->default('ar')
                                            ->required()
                                            ->rules(['required', 'in:ar,en'])
                                            ->validationMessages([
                                                'required' => 'اللغة المفضلة مطلوبة',
                                                'in' => 'يرجى اختيار لغة صحيحة',
                                            ]),
                                    ])->columns(2),

                                Forms\Components\Section::make('حالة التحقق')
                                    ->schema([
                                        Forms\Components\Toggle::make('is_phone_verified')
                                            ->label('تم التحقق من الهاتف')
                                            ->default(false),
                                        Forms\Components\DateTimePicker::make('phone_verified_at')
                                            ->label('تاريخ التحقق من الهاتف'),
                                    ])->columns(2),
                            ]),

                        Forms\Components\Tabs\Tab::make('معلومات الاشتراك')
                            ->icon('heroicon-o-credit-card')
                            ->schema([
                                Forms\Components\Section::make('تفاصيل الاشتراك')
                                    ->schema([
                                        Forms\Components\Select::make('subscription_plan_id')
                                            ->label('خطة الاشتراك')
                                            ->relationship('subscriptionPlan', 'name')
                                            ->searchable()
                                            ->preload(),
                                        Forms\Components\Select::make('subscription_status')
                                            ->label('حالة الاشتراك')
                                            ->options([
                                                'active' => 'نشط',
                                                'expired' => 'منتهي الصلاحية',
                                                'cancelled' => 'ملغي',
                                                'pending' => 'في الانتظار',
                                            ])
                                            ->required()
                                            ->rules(['required', 'in:active,expired,cancelled,pending'])
                                            ->validationMessages([
                                                'required' => 'حالة الاشتراك مطلوبة',
                                                'in' => 'يرجى اختيار حالة صحيحة',
                                            ]),
                                        Forms\Components\DateTimePicker::make('subscription_starts_at')
                                            ->label('تاريخ بداية الاشتراك'),
                                        Forms\Components\DateTimePicker::make('subscription_ends_at')
                                            ->label('تاريخ انتهاء الاشتراك'),
                                        Forms\Components\TextInput::make('subscription_amount')
                                            ->label('مبلغ الاشتراك')
                                            ->numeric()
                                            ->prefix('ر.س')
                                            ->minValue(0)
                                            ->rules(['nullable', 'numeric', 'min:0'])
                                            ->validationMessages([
                                                'numeric' => 'مبلغ الاشتراك يجب أن يكون رقم',
                                                'min' => 'مبلغ الاشتراك لا يمكن أن يكون أقل من صفر',
                                            ]),
                                        Forms\Components\Toggle::make('is_subscription_paid')
                                            ->label('تم دفع الاشتراك')
                                            ->default(false),
                                    ])->columns(2),
                            ]),

                        Forms\Components\Tabs\Tab::make('معلومات العمل')
                            ->icon('heroicon-o-building-office')
                            ->schema([
                                Forms\Components\Section::make('بيانات العمل الأساسية')
                                    ->schema([
                                        Forms\Components\TextInput::make('business_name')
                                            ->label('اسم العمل')
                                            ->maxLength(255)
                                            ->rules(['nullable', 'string', 'max:255'])
                                            ->validationMessages([
                                                'max' => 'اسم العمل لا يمكن أن يتجاوز 255 حرف',
                                            ]),
                                        Forms\Components\Select::make('business_type')
                                            ->label('نوع العمل')
                                            ->options([
                                                'restaurant' => 'مطعم',
                                                'cafe' => 'مقهى',
                                                'bakery' => 'مخبز',
                                                'fast_food' => 'وجبات سريعة',
                                            ])
                                            ->rules(['nullable', 'in:restaurant,cafe,bakery,fast_food'])
                                            ->validationMessages([
                                                'in' => 'يرجى اختيار نوع عمل صحيح',
                                            ]),
                                        Forms\Components\Textarea::make('business_description')
                                            ->label('وصف العمل')
                                            ->rows(3)
                                            ->maxLength(1000)
                                            ->rules(['nullable', 'string', 'max:1000'])
                                            ->validationMessages([
                                                'max' => 'وصف العمل لا يمكن أن يتجاوز 1000 حرف',
                                            ]),
                                        Forms\Components\TextInput::make('business_phone')
                                            ->label('هاتف العمل')
                                            ->tel()
                                            ->maxLength(191)
                                            ->rules(['nullable', 'string', 'max:191'])
                                            ->validationMessages([
                                                'max' => 'هاتف العمل لا يمكن أن يتجاوز 191 حرف',
                                            ]),
                                        Forms\Components\TextInput::make('business_email')
                                            ->label('بريد العمل الإلكتروني')
                                            ->email()
                                            ->maxLength(191)
                                            ->rules(['nullable', 'email', 'max:191'])
                                            ->validationMessages([
                                                'email' => 'يرجى إدخال بريد إلكتروني صحيح',
                                                'max' => 'بريد العمل لا يمكن أن يتجاوز 191 حرف',
                                            ]),
                                    ])->columns(2),
                            ]),

                        Forms\Components\Tabs\Tab::make('الحالة والموافقات')
                            ->icon('heroicon-o-check-circle')
                            ->schema([
                                Forms\Components\Section::make('حالة التاجر')
                                    ->schema([
                                        Forms\Components\Select::make('status')
                                            ->label('الحالة')
                                            ->options([
                                                'pending' => 'في الانتظار',
                                                'active' => 'نشط',
                                                'suspended' => 'معلق',
                                                'rejected' => 'مرفوض',
                                            ])
                                            ->required()
                                            ->rules(['required', 'in:pending,active,suspended,rejected'])
                                            ->validationMessages([
                                                'required' => 'الحالة مطلوبة',
                                                'in' => 'يرجى اختيار حالة صحيحة',
                                            ]),
                                        Forms\Components\Toggle::make('is_verified')
                                            ->label('تم التحقق')
                                            ->default(false),
                                        Forms\Components\Toggle::make('is_approved')
                                            ->label('تمت الموافقة')
                                            ->default(false),
                                        Forms\Components\Toggle::make('is_featured')
                                            ->label('مميز')
                                            ->default(false),
                                        Forms\Components\DateTimePicker::make('approved_at')
                                            ->label('تاريخ الموافقة'),
                                        Forms\Components\Textarea::make('rejection_reason')
                                            ->label('سبب الرفض')
                                            ->rows(3)
                                            ->maxLength(500)
                                            ->columnSpanFull()
                                            ->rules(['nullable', 'string', 'max:500'])
                                            ->validationMessages([
                                                'max' => 'سبب الرفض لا يمكن أن يتجاوز 500 حرف',
                                            ]),
                                    ])->columns(2),
                            ]),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('الاسم')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn ($state) => is_array($state) ? ($state['ar'] ?? $state['en'] ?? 'غير محدد') : $state)
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('business_name')
                    ->label('اسم العمل')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn ($state) => is_array($state) ? ($state['ar'] ?? $state['en'] ?? 'غير محدد') : $state)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('phone_number')
                    ->label('رقم الهاتف')
                    ->searchable()
                    ->copyable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('business_type')
                    ->label('نوع العمل')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'restaurant' => 'مطعم',
                        'cafe' => 'مقهى',
                        'bakery' => 'مخبز',
                        'fast_food' => 'وجبات سريعة',
                        default => $state,
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'restaurant' => 'success',
                        'cafe' => 'info',
                        'bakery' => 'warning',
                        'fast_food' => 'danger',
                        default => 'gray',
                    })
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('subscriptionPlan.name')
                    ->label('خطة الاشتراك')
                    ->formatStateUsing(fn ($state) => is_array($state) ? ($state['ar'] ?? $state['en'] ?? 'غير محدد') : ($state ?? 'غير محدد'))
                    ->badge()
                    ->color('primary')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('subscription_status')
                    ->label('حالة الاشتراك')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'active' => 'نشط',
                        'expired' => 'منتهي الصلاحية',
                        'cancelled' => 'ملغي',
                        'pending' => 'في الانتظار',
                        default => $state,
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'expired' => 'danger',
                        'cancelled' => 'gray',
                        'pending' => 'warning',
                        default => 'gray',
                    })
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_phone_verified')
                    ->label('تم التحقق من الهاتف')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_approved')
                    ->label('تمت الموافقة')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'active' => 'نشط',
                        'pending' => 'في الانتظار',
                        'suspended' => 'معلق',
                        'rejected' => 'مرفوض',
                        default => $state,
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'pending' => 'warning',
                        'suspended' => 'danger',
                        'rejected' => 'gray',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('orders_count')
                    ->label('عدد الطلبات')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('average_rating')
                    ->label('التقييم')
                    ->numeric(decimalPlaces: 1)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ التسجيل')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('business_type')
                    ->label('نوع العمل')
                    ->options([
                        'restaurant' => 'مطعم',
                        'cafe' => 'مقهى',
                        'bakery' => 'مخبز',
                        'fast_food' => 'وجبات سريعة',
                    ]),
                SelectFilter::make('subscription_status')
                    ->label('حالة الاشتراك')
                    ->options([
                        'active' => 'نشط',
                        'expired' => 'منتهي الصلاحية',
                        'cancelled' => 'ملغي',
                        'pending' => 'في الانتظار',
                    ]),
                SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        'pending' => 'في الانتظار',
                        'active' => 'نشط',
                        'suspended' => 'معلق',
                        'rejected' => 'مرفوض',
                    ]),
                Filter::make('is_approved')
                    ->label('تمت الموافقة')
                    ->query(fn (Builder $query): Builder => $query->where('is_approved', true)),
                Filter::make('is_verified')
                    ->label('تم التحقق')
                    ->query(fn (Builder $query): Builder => $query->where('is_verified', true)),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('عرض'),
                Tables\Actions\EditAction::make()
                    ->label('تعديل')
                    ->modal()
                    ->modalHeading('تعديل التاجر')
                    ->modalSubmitActionLabel('حفظ التغييرات')
                    ->modalCancelActionLabel('إلغاء'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('حذف المحدد'),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(25);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ProductsRelationManager::class,
            RelationManagers\OrdersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMerchants::route('/'),
            'create' => Pages\CreateMerchant::route('/create'),
            'view' => Pages\ViewMerchant::route('/{record}'),
            'edit' => Pages\EditMerchant::route('/{record}/edit'),
        ];
    }
}
