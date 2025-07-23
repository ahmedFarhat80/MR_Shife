<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FoodNationalityResource\Pages;
use App\Models\FoodNationality;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use App\Helpers\CacheHelper;

class FoodNationalityResource extends Resource
{
    protected static ?string $model = FoodNationality::class;

    protected static ?string $navigationIcon = 'heroicon-o-flag';

    protected static ?string $navigationGroup = null;

    protected static ?string $navigationLabel = null;

    protected static ?string $modelLabel = null;

    protected static ?string $pluralModelLabel = null;

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.content_management');
    }

    public static function getNavigationLabel(): string
    {
        return __('food_nationality.title');
    }

    public static function getModelLabel(): string
    {
        return __('food_nationality.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('food_nationality.title');
    }

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات الجنسية الأساسية')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('name.ar')
                                    ->label('اسم الجنسية (عربي)')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('name.en')
                                    ->label('اسم الجنسية (إنجليزي)')
                                    ->required()
                                    ->maxLength(255),
                            ]),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Textarea::make('description.ar')
                                    ->label('وصف الجنسية (عربي)')
                                    ->rows(3)
                                    ->maxLength(500),
                                Forms\Components\Textarea::make('description.en')
                                    ->label('وصف الجنسية (إنجليزي)')
                                    ->rows(3)
                                    ->maxLength(500),
                            ]),
                    ]),

                Forms\Components\Section::make('إعدادات إضافية')
                    ->schema([
                        Forms\Components\FileUpload::make('icon')
                            ->label('أيقونة الجنسية')
                            ->image()
                            ->directory('nationality-icons')
                            ->disk('public')
                            ->visibility('public')
                            ->imageEditor()
                            ->imageEditorAspectRatios([
                                '1:1',
                                '4:3',
                                '16:9',
                            ])
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'])
                            ->maxSize(2048) // 2MB
                            ->getUploadedFileNameForStorageUsing(
                                fn (TemporaryUploadedFile $file): string => (string) str($file->getClientOriginalName())
                                    ->prepend('nationality-')
                                    ->prepend(now()->timestamp . '-'),
                            )
                            ->downloadable()
                            ->previewable()
                            ->openable()
                            ->deletable()
                            ->reorderable()
                            ->imagePreviewHeight('150')
                            ->loadingIndicatorPosition('center')
                            ->panelAspectRatio('2:1')
                            ->panelLayout('integrated')
                            ->removeUploadedFileButtonPosition('right')
                            ->uploadButtonPosition('left')
                            ->uploadProgressIndicatorPosition('left'),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('sort_order')
                                    ->label('ترتيب العرض')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->step(1),
                                Forms\Components\Toggle::make('is_active')
                                    ->label('نشط')
                                    ->default(true),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('icon')
                    ->label('الأيقونة')
                    ->circular()
                    ->size(50)
                    ->disk('public')
                    ->getStateUsing(function ($record) {
                        if ($record->icon) {
                            return str_replace('\\', '/', $record->icon);
                        }
                        return null;
                    })
                    ->defaultImageUrl('https://ui-avatars.com/api/?name=Nationality&background=2563EB&color=fff&size=200'),

                Tables\Columns\TextColumn::make('name')
                    ->label('اسم الجنسية')
                    ->formatStateUsing(function ($record) {
                        $name = $record->name;
                        if (is_array($name)) {
                            return $name['ar'] ?? $name['en'] ?? 'غير محدد';
                        }
                        return $name ?? 'غير محدد';
                    })
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('products_count')
                    ->label('عدد المنتجات')
                    ->counts('products')
                    ->badge()
                    ->color('success'),

                Tables\Columns\TextColumn::make('sort_order')
                    ->label('الترتيب')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('الحالة')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('تاريخ التحديث')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('الحالة')
                    ->placeholder('جميع الجنسيات')
                    ->trueLabel('نشط فقط')
                    ->falseLabel('غير نشط فقط'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('عرض'),
                Tables\Actions\EditAction::make()
                    ->label('تعديل')
                    ->after(function () {
                        CacheHelper::clearNationalities();
                    }),
                Tables\Actions\DeleteAction::make()
                    ->label('حذف')
                    ->after(function () {
                        CacheHelper::clearNationalities();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('حذف المحدد')
                        ->after(function () {
                            CacheHelper::clearNationalities();
                        }),
                ]),
            ])
            ->defaultSort('sort_order', 'asc')
            ->striped()
            ->paginated([10, 25, 50, 100]);
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
            'index' => Pages\ListFoodNationalities::route('/'),
            'create' => Pages\CreateFoodNationality::route('/create'),
            'view' => Pages\ViewFoodNationality::route('/{record}'),
            'edit' => Pages\EditFoodNationality::route('/{record}/edit'),
        ];
    }
}
