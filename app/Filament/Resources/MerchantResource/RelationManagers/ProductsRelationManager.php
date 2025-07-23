<?php

namespace App\Filament\Resources\MerchantResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProductsRelationManager extends RelationManager
{
    protected static string $relationship = 'products';

    protected static ?string $title = 'منتجات التاجر';

    protected static ?string $modelLabel = 'منتج';

    protected static ?string $pluralModelLabel = 'المنتجات';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات المنتج الأساسية')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('اسم المنتج')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('description')
                            ->label('وصف المنتج')
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\Select::make('internal_category_id')
                            ->label('الفئة الداخلية')
                            ->relationship('internalCategory', 'name')
                            ->searchable()
                            ->preload(),

                        Forms\Components\Select::make('food_nationality_id')
                            ->label('جنسية الطعام')
                            ->relationship('foodNationality', 'name')
                            ->searchable()
                            ->preload(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('التسعير والتوفر')
                    ->schema([
                        Forms\Components\TextInput::make('base_price')
                            ->label('السعر الأساسي')
                            ->required()
                            ->numeric()
                            ->prefix('ر.س'),

                        Forms\Components\TextInput::make('discount_percentage')
                            ->label('نسبة الخصم (%)')
                            ->numeric()
                            ->suffix('%')
                            ->minValue(0)
                            ->maxValue(100),

                        Forms\Components\TextInput::make('discounted_price')
                            ->label('السعر بعد الخصم')
                            ->numeric()
                            ->prefix('ر.س')
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\Toggle::make('is_available')
                            ->label('متوفر')
                            ->default(true),

                        Forms\Components\TextInput::make('preparation_time')
                            ->label('وقت التحضير (دقيقة)')
                            ->required()
                            ->numeric()
                            ->default(15)
                            ->suffix('دقيقة'),

                        Forms\Components\TextInput::make('sku')
                            ->label('رمز المنتج (SKU)')
                            ->maxLength(191)
                            ->unique(ignoreRecord: true),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('المعلومات الغذائية')
                    ->schema([
                        Forms\Components\TextInput::make('calories')
                            ->label('السعرات الحرارية')
                            ->numeric()
                            ->suffix('سعرة'),

                        Forms\Components\Textarea::make('ingredients')
                            ->label('المكونات')
                            ->rows(2),

                        Forms\Components\Textarea::make('allergens')
                            ->label('مسببات الحساسية')
                            ->rows(2),
                    ])
                    ->columns(1),

                Forms\Components\Section::make('خصائص المنتج')
                    ->schema([
                        Forms\Components\Toggle::make('is_vegetarian')
                            ->label('نباتي'),

                        Forms\Components\Toggle::make('is_vegan')
                            ->label('نباتي صرف'),

                        Forms\Components\Toggle::make('is_gluten_free')
                            ->label('خالي من الجلوتين'),

                        Forms\Components\Toggle::make('is_spicy')
                            ->label('حار'),

                        Forms\Components\Toggle::make('is_featured')
                            ->label('مميز'),

                        Forms\Components\Toggle::make('is_popular')
                            ->label('شائع'),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('إدارة المخزون')
                    ->schema([
                        Forms\Components\Toggle::make('track_stock')
                            ->label('تتبع المخزون')
                            ->live(),

                        Forms\Components\TextInput::make('stock_quantity')
                            ->label('كمية المخزون')
                            ->numeric()
                            ->default(0)
                            ->visible(fn (Forms\Get $get) => $get('track_stock')),

                        Forms\Components\TextInput::make('sort_order')
                            ->label('ترتيب العرض')
                            ->numeric()
                            ->default(0),
                    ])
                    ->columns(3),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\ImageColumn::make('image')
                    ->label('الصورة')
                    ->circular()
                    ->defaultImageUrl(function ($record) {
                        return 'https://ui-avatars.com/api/?name=' . urlencode($record->name) . '&background=794E24&color=fff&size=200';
                    }),

                Tables\Columns\TextColumn::make('name')
                    ->label('اسم المنتج')
                    ->searchable()
                    ->sortable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('internalCategory.name')
                    ->label('الفئة')
                    ->badge()
                    ->color('info')
                    ->sortable(),

                Tables\Columns\TextColumn::make('base_price')
                    ->label('السعر')
                    ->money('SAR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('discounted_price')
                    ->label('السعر بعد الخصم')
                    ->money('SAR')
                    ->sortable()
                    ->color('success'),

                Tables\Columns\IconColumn::make('is_available')
                    ->label('متوفر')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('preparation_time')
                    ->label('وقت التحضير')
                    ->suffix(' دقيقة')
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_orders')
                    ->label('إجمالي الطلبات')
                    ->numeric()
                    ->sortable()
                    ->color('warning'),

                Tables\Columns\TextColumn::make('average_rating')
                    ->label('التقييم')
                    ->numeric(decimalPlaces: 1)
                    ->suffix(' ⭐')
                    ->sortable(),

                Tables\Columns\TextColumn::make('stock_quantity')
                    ->label('المخزون')
                    ->numeric()
                    ->sortable()
                    ->visible(fn ($record) => $record && $record->track_stock),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإضافة')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('internal_category_id')
                    ->label('الفئة')
                    ->relationship('internalCategory', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\TernaryFilter::make('is_available')
                    ->label('متوفر')
                    ->placeholder('الكل')
                    ->trueLabel('متوفر')
                    ->falseLabel('غير متوفر'),

                Tables\Filters\TernaryFilter::make('is_featured')
                    ->label('مميز')
                    ->placeholder('الكل')
                    ->trueLabel('مميز')
                    ->falseLabel('عادي'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('إضافة منتج جديد')
                    ->icon('heroicon-o-plus'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('تعديل'),
                Tables\Actions\DeleteAction::make()
                    ->label('حذف'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('حذف المحدد'),
                ]),
            ])
            ->defaultSort('sort_order', 'asc')
            ->reorderable('sort_order')
            ->emptyStateHeading('لا توجد منتجات')
            ->emptyStateDescription('لم يتم إضافة أي منتجات لهذا التاجر بعد.')
            ->emptyStateIcon('heroicon-o-cube');
    }
}
