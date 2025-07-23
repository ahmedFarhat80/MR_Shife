<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AdminResource\Pages;
use App\Filament\Resources\AdminResource\RelationManagers;
use App\Models\Admin;
use App\Services\AdminTranslationService;
use Spatie\Permission\Models\Role;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;

class AdminResource extends Resource
{
    protected static ?string $model = Admin::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

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
        return __('admin.title');
    }

    public static function getModelLabel(): string
    {
        return __('admin.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.title');
    }

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات المدير')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('الاسم')
                            ->required()
                            ->maxLength(191)
                            ->minLength(2)
                            ->rules(['required', 'string', 'min:2', 'max:191'])
                            ->validationMessages([
                                'required' => 'الاسم مطلوب',
                                'min' => 'الاسم يجب أن يكون على الأقل حرفين',
                                'max' => 'الاسم لا يمكن أن يتجاوز 191 حرف',
                            ]),
                        Forms\Components\TextInput::make('email')
                            ->label('البريد الإلكتروني')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(191)
                            ->validationMessages([
                                'required' => 'البريد الإلكتروني مطلوب',
                                'email' => 'يرجى إدخال بريد إلكتروني صحيح',
                                'unique' => 'هذا البريد الإلكتروني مستخدم بالفعل',
                                'max' => 'البريد الإلكتروني لا يمكن أن يتجاوز 191 حرف',
                            ]),
                        Forms\Components\TextInput::make('password')
                            ->label('كلمة المرور')
                            ->password()
                            ->required(fn (string $context): bool => $context === 'create')
                            ->minLength(8)
                            ->maxLength(191)
                            ->validationMessages([
                                'required' => 'كلمة المرور مطلوبة',
                                'min' => 'كلمة المرور يجب أن تكون على الأقل 8 أحرف',
                                'max' => 'كلمة المرور لا يمكن أن تتجاوز 191 حرف',
                            ])
                            ->dehydrateStateUsing(fn ($state) => filled($state) ? bcrypt($state) : null)
                            ->dehydrated(fn ($state) => filled($state))
                            ->helperText('اتركه فارغاً إذا كنت لا تريد تغيير كلمة المرور'),
                        Forms\Components\Toggle::make('is_active')
                            ->label('نشط')
                            ->default(true),

                        Forms\Components\Select::make('roles')
                            ->label('الأدوار والصلاحيات')
                            ->multiple()
                            ->relationship('roles', 'name')
                            ->required()
                            ->preload()
                            ->searchable()
                            ->optionsLimit(50)
                            ->helperText('يجب اختيار دور واحد على الأقل لهذا المدير')
                            ->columnSpanFull()
                            ->options(function () {
                                return Role::where('guard_name', 'admin')->pluck('name', 'id')->toArray();
                            })
                            ->validationMessages(AdminTranslationService::getValidationMessages())
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->label('اسم الدور الجديد')
                                    ->required()
                                    ->unique('roles', 'name')
                                    ->maxLength(191)
                                    ->helperText('سيتم إنشاء دور جديد بهذا الاسم'),
                                Forms\Components\Hidden::make('guard_name')
                                    ->default('admin'),
                            ])
                            ->createOptionUsing(function (array $data) {
                                $role = \Spatie\Permission\Models\Role::create([
                                    'name' => $data['name'],
                                    'guard_name' => 'admin',
                                ]);

                                \Filament\Notifications\Notification::make()
                                    ->title('تم إنشاء دور جديد')
                                    ->body("تم إنشاء الدور {$role->name} بنجاح")
                                    ->success()
                                    ->send();

                                return $role->id;
                            }),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(
                Admin::query()
                    ->select('id', 'name', 'email', 'is_active', 'created_at')
                    ->with(['roles:id,name'])
            )
            ->defaultPaginationPageOption(10)
            ->deferLoading()
            ->poll(null)
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('الاسم')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->badge()
                    ->color(fn ($record) => $record->email === 'admin@mrshife.com' ? 'danger' : 'primary')
                    ->formatStateUsing(function ($state, $record) {
                        return $record->email === 'admin@mrshife.com'
                            ? $state . ' (سوبر أدمن)'
                            : $state;
                    }),
                Tables\Columns\TextColumn::make('email')
                    ->label('البريد الإلكتروني')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('نشط')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('roles.name')
                    ->label('الأدوار والصلاحيات')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'super_admin' => 'danger',
                        'مدير عام' => 'success',
                        'مدير محتوى' => 'info',
                        'مدير عملاء' => 'warning',
                        default => 'gray',
                    })
                    ->separator(' ')
                    ->wrap()
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'super_admin' => 'سوبر أدمن',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('is_active')
                    ->label('الحالة')
                    ->options([
                        1 => 'نشط',
                        0 => 'غير نشط',
                    ]),
                SelectFilter::make('roles')
                    ->label('الأدوار')
                    ->relationship('roles', 'name')
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->optionsLimit(50),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('عرض')
                    ->modal()
                    ->modalHeading('عرض تفاصيل المدير')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('إغلاق'),
                Tables\Actions\EditAction::make()
                    ->label('تعديل')
                    ->modal()
                    ->modalHeading('تعديل المدير')
                    ->modalSubmitActionLabel('حفظ التغييرات')
                    ->modalCancelActionLabel('إلغاء')
                    ->visible(fn ($record) => $record && $record->email !== 'admin@mrshife.com')
                    ->tooltip(fn ($record) => $record && $record->email === 'admin@mrshife.com'
                        ? 'لا يمكن تعديل السوبر أدمن'
                        : null),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('حذف المحدد')
                        ->action(function ($records) {
                            // منع حذف السوبر أدمن
                            $superAdmin = $records->where('email', 'admin@mrshife.com')->first();
                            if ($superAdmin) {
                                \Filament\Notifications\Notification::make()
                                    ->title('خطأ')
                                    ->body('لا يمكن حذف السوبر أدمن')
                                    ->danger()
                                    ->send();
                                return;
                            }

                            // حذف باقي المديرين
                            $records->where('email', '!=', 'admin@mrshife.com')->each->delete();
                        }),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\RolesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAdmins::route('/'),
            'create' => Pages\CreateAdmin::route('/create'),
            'edit' => Pages\EditAdmin::route('/{record}/edit'),
        ];
    }


}
