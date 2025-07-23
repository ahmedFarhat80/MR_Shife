<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InternalCategoryResource\Pages;
use App\Models\InternalCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use App\Helpers\CacheHelper;

class InternalCategoryResource extends Resource
{
    protected static ?string $model = InternalCategory::class;

    protected static ?string $navigationIcon = 'heroicon-o-folder';

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
        return __('internal_category.title');
    }

    public static function getModelLabel(): string
    {
        return __('internal_category.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('internal_category.title');
    }

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات الفئة الأساسية')
                    ->schema([


                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('name.ar')
                                    ->label('اسم الفئة (عربي)')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('name.en')
                                    ->label('اسم الفئة (إنجليزي)')
                                    ->required()
                                    ->maxLength(255),
                            ]),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Textarea::make('description.ar')
                                    ->label('وصف الفئة (عربي)')
                                    ->rows(3)
                                    ->maxLength(500),
                                Forms\Components\Textarea::make('description.en')
                                    ->label('وصف الفئة (إنجليزي)')
                                    ->rows(3)
                                    ->maxLength(500),
                            ]),
                    ]),

                Forms\Components\Section::make('إعدادات إضافية')
                    ->schema([
                        Forms\Components\FileUpload::make('image')
                            ->label('صورة الفئة')
                            ->image()
                            ->directory('category-images')
                            ->disk('public')
                            ->visibility('public')
                            ->imageEditor()
                            ->imageEditorAspectRatios([
                                '16:9',
                                '4:3',
                                '1:1',
                            ])
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/gif', 'image/webp'])
                            ->maxSize(5120) // 5MB
                            ->getUploadedFileNameForStorageUsing(
                                fn (TemporaryUploadedFile $file): string => (string) str($file->getClientOriginalName())
                                    ->prepend('category-')
                                    ->prepend(now()->timestamp . '-'),
                            )
                            ->downloadable()
                            ->previewable()
                            ->openable()
                            ->deletable()
                            ->reorderable()
                            ->imagePreviewHeight('200')
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
                Tables\Columns\ImageColumn::make('image')
                    ->label('الصورة')
                    ->circular()
                    ->size(50)
                    ->disk('public')
                    ->getStateUsing(function ($record) {
                        if ($record->image) {
                            // تطبيع مسار الصورة لاستخدام forward slashes
                            return str_replace('\\', '/', $record->image);
                        }
                        return null;
                    })
                    ->defaultImageUrl('https://ui-avatars.com/api/?name=Category&background=794E24&color=fff&size=200'),

                Tables\Columns\TextColumn::make('name')
                    ->label('اسم الفئة')
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
                    ->placeholder('جميع الفئات')
                    ->trueLabel('نشط فقط')
                    ->falseLabel('غير نشط فقط'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('عرض'),
                Tables\Actions\EditAction::make()
                    ->label('تعديل')
                    ->after(function () {
                        // مسح الكاش بعد التعديل
                        CacheHelper::clearCategories();
                    }),
                Tables\Actions\DeleteAction::make()
                    ->label('حذف')
                    ->after(function () {
                        // مسح الكاش بعد الحذف
                        CacheHelper::clearCategories();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('حذف المحدد')
                        ->after(function () {
                            // مسح الكاش بعد الحذف المجمع
                            CacheHelper::clearCategories();
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
            'index' => Pages\ListInternalCategories::route('/'),
            'create' => Pages\CreateInternalCategory::route('/create'),
            'view' => Pages\ViewInternalCategory::route('/{record}'),
            'edit' => Pages\EditInternalCategory::route('/{record}/edit'),
        ];
    }
}
