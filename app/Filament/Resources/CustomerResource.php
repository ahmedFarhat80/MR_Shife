<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerResource\Pages;
use App\Filament\Resources\CustomerResource\RelationManagers;
use App\Models\Customer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

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
        return __('customer.title');
    }

    public static function getModelLabel(): string
    {
        return __('customer.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('customer.title');
    }

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('المعلومات الأساسية')
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
                            ->rules(['required', 'string', 'max:191', 'unique:customers,phone_number'])
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
                            ->rules(['nullable', 'email', 'max:191', 'unique:customers,email'])
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
                        Forms\Components\Select::make('gender')
                            ->label('الجنس')
                            ->options([
                                'male' => 'ذكر',
                                'female' => 'أنثى',
                            ])
                            ->rules(['nullable', 'in:male,female'])
                            ->validationMessages([
                                'in' => 'يرجى اختيار جنس صحيح',
                            ]),
                        Forms\Components\DatePicker::make('date_of_birth')
                            ->label('تاريخ الميلاد')
                            ->maxDate(now()->subYears(13))
                            ->rules(['nullable', 'date', 'before:' . now()->subYears(13)->format('Y-m-d')])
                            ->validationMessages([
                                'date' => 'يرجى إدخال تاريخ صحيح',
                                'before' => 'يجب أن يكون العمر 13 سنة على الأقل',
                            ]),
                    ])->columns(2),

                Forms\Components\Section::make('حالة الحساب')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('حالة الحساب')
                            ->options([
                                'active' => 'نشط',
                                'inactive' => 'غير نشط',
                                'suspended' => 'معلق',
                                'banned' => 'محظور',
                            ])
                            ->default('active')
                            ->required()
                            ->rules(['required', 'in:active,inactive,suspended,banned'])
                            ->validationMessages([
                                'required' => 'حالة الحساب مطلوبة',
                                'in' => 'يرجى اختيار حالة صحيحة',
                            ]),
                        Forms\Components\Toggle::make('phone_verified')
                            ->label('تم التحقق من الهاتف')
                            ->default(false),
                        Forms\Components\Toggle::make('email_verified')
                            ->label('تم التحقق من البريد الإلكتروني')
                            ->default(false),
                        Forms\Components\TextInput::make('loyalty_points')
                            ->label('نقاط الولاء')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->rules(['nullable', 'integer', 'min:0'])
                            ->validationMessages([
                                'integer' => 'نقاط الولاء يجب أن تكون رقم صحيح',
                                'min' => 'نقاط الولاء لا يمكن أن تكون أقل من صفر',
                            ]),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('avatar')
                    ->label('الصورة')
                    ->circular()
                    ->defaultImageUrl(fn ($record) =>
                        'https://ui-avatars.com/api/?name=' . urlencode(
                            is_array($record->name) ? ($record->name['ar'] ?? $record->name['en'] ?? 'عميل') : ($record->name ?? 'عميل')
                        ) . '&background=794E24&color=fff&size=200'
                    )
                    ->toggleable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('الاسم')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn ($state) => is_array($state) ? ($state['ar'] ?? $state['en'] ?? 'غير محدد') : $state)
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('phone_number')
                    ->label('رقم الهاتف')
                    ->searchable()
                    ->copyable()
                    ->icon('heroicon-m-phone')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('البريد الإلكتروني')
                    ->searchable()
                    ->copyable()
                    ->icon('heroicon-m-envelope')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('preferred_language')
                    ->label('اللغة المفضلة')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'ar' => 'العربية',
                        'en' => 'English',
                        default => $state,
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'ar' => 'success',
                        'en' => 'info',
                        default => 'gray',
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('gender')
                    ->label('الجنس')
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'male' => 'ذكر',
                        'female' => 'أنثى',
                        default => 'غير محدد',
                    })
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'male' => 'blue',
                        'female' => 'pink',
                        default => 'gray',
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('phone_verified')
                    ->label('تم التحقق من الهاتف')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->toggleable(),

                Tables\Columns\IconColumn::make('email_verified')
                    ->label('تم التحقق من البريد')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('loyalty_points')
                    ->label('نقاط الولاء')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color('warning')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'active' => 'نشط',
                        'inactive' => 'غير نشط',
                        'suspended' => 'معلق',
                        'banned' => 'محظور',
                        default => $state,
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'gray',
                        'suspended' => 'warning',
                        'banned' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('date_of_birth')
                    ->label('تاريخ الميلاد')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ التسجيل')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('حالة الحساب')
                    ->options([
                        'active' => 'نشط',
                        'inactive' => 'غير نشط',
                        'suspended' => 'معلق',
                        'banned' => 'محظور',
                    ]),
                SelectFilter::make('preferred_language')
                    ->label('اللغة المفضلة')
                    ->options([
                        'ar' => 'العربية',
                        'en' => 'English',
                    ]),
                Filter::make('phone_verified')
                    ->label('تم التحقق من الهاتف')
                    ->query(fn (Builder $query): Builder => $query->where('phone_verified', true)),
                Filter::make('email_verified')
                    ->label('تم التحقق من البريد الإلكتروني')
                    ->query(fn (Builder $query): Builder => $query->where('email_verified', true)),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('عرض')
                    ->modal()
                    ->modalHeading('عرض تفاصيل العميل')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('إغلاق'),
                Tables\Actions\EditAction::make()
                    ->label('تعديل')
                    ->modal()
                    ->modalHeading('تعديل العميل')
                    ->modalSubmitActionLabel('حفظ التغييرات')
                    ->modalCancelActionLabel('إلغاء'),
                Tables\Actions\Action::make('suspend')
                    ->label('تعليق')
                    ->icon('heroicon-o-pause')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(fn (Customer $record) => $record->update(['status' => 'suspended']))
                    ->visible(fn (Customer $record) => $record->status === 'active'),
                Tables\Actions\Action::make('activate')
                    ->label('تفعيل')
                    ->icon('heroicon-o-play')
                    ->color('success')
                    ->action(fn (Customer $record) => $record->update(['status' => 'active']))
                    ->visible(fn (Customer $record) => in_array($record->status, ['inactive', 'suspended'])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('حذف المحدد'),
                    Tables\Actions\BulkAction::make('suspend')
                        ->label('تعليق المحدد')
                        ->icon('heroicon-o-pause')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(fn ($records) => $records->each->update(['status' => 'suspended'])),
                    Tables\Actions\BulkAction::make('activate')
                        ->label('تفعيل المحدد')
                        ->icon('heroicon-o-play')
                        ->color('success')
                        ->action(fn ($records) => $records->each->update(['status' => 'active'])),
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomers::route('/'),
            'create' => Pages\CreateCustomer::route('/create'),
            'view' => Pages\ViewCustomer::route('/{record}'),
            'edit' => Pages\EditCustomer::route('/{record}/edit'),
        ];
    }
}
