<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    // Hide from navigation - accessible only through merchant profiles
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $navigationGroup = null;

    protected static ?string $modelLabel = null;

    protected static ?string $pluralModelLabel = null;

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.content_management');
    }

    public static function getModelLabel(): string
    {
        return __('product.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('product.title');
    }

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('merchant_id')
                    ->relationship('merchant', 'name')
                    ->required(),
                Forms\Components\Select::make('internal_category_id')
                    ->relationship('internalCategory', 'name'),
                Forms\Components\Select::make('food_nationality_id')
                    ->relationship('foodNationality', 'name'),
                Forms\Components\TextInput::make('name')
                    ->required(),
                Forms\Components\TextInput::make('description'),
                Forms\Components\TextInput::make('background_type')
                    ->required(),
                Forms\Components\TextInput::make('background_value')
                    ->maxLength(191),
                Forms\Components\TextInput::make('base_price')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('discount_percentage')
                    ->numeric(),
                Forms\Components\TextInput::make('discounted_price')
                    ->numeric(),
                Forms\Components\Toggle::make('is_available')
                    ->required(),
                Forms\Components\TextInput::make('preparation_time')
                    ->required()
                    ->numeric()
                    ->default(15),
                Forms\Components\TextInput::make('sku')
                    ->label('SKU')
                    ->maxLength(191),
                Forms\Components\TextInput::make('calories')
                    ->numeric(),
                Forms\Components\TextInput::make('ingredients'),
                Forms\Components\TextInput::make('allergens'),
                Forms\Components\Toggle::make('is_vegetarian')
                    ->required(),
                Forms\Components\Toggle::make('is_vegan')
                    ->required(),
                Forms\Components\Toggle::make('is_gluten_free')
                    ->required(),
                Forms\Components\Toggle::make('is_spicy')
                    ->required(),
                Forms\Components\Toggle::make('is_featured')
                    ->required(),
                Forms\Components\Toggle::make('is_popular')
                    ->required(),
                Forms\Components\TextInput::make('total_orders')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('average_rating')
                    ->required()
                    ->numeric()
                    ->default(0.00),
                Forms\Components\TextInput::make('sort_order')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('stock_quantity')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\Toggle::make('track_stock')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('background_value')
                    ->label(__('field.image'))
                    ->circular()
                    ->defaultImageUrl('/images/placeholder-product.png')
                    ->visible(fn ($record) => $record->background_type === 'image')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('name')
                    ->label(__('product.name'))
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn ($state) => is_array($state) ? ($state['ar'] ?? $state['en'] ?? __('common.not_specified')) : $state)
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('merchant.business_name')
                    ->label(__('field.merchant'))
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn ($state) => is_array($state) ? ($state['ar'] ?? $state['en'] ?? __('common.not_specified')) : $state)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('internalCategory.name')
                    ->label(__('field.category'))
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn ($state) => is_array($state) ? ($state['ar'] ?? $state['en'] ?? __('common.not_specified')) : $state)
                    ->badge()
                    ->color('primary')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('foodNationality.name')
                    ->label(__('filter.nationality'))
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn ($state) => is_array($state) ? ($state['ar'] ?? $state['en'] ?? __('common.not_specified')) : $state)
                    ->badge()
                    ->color('info')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('base_price')
                    ->label(__('product.base_price'))
                    ->money('SAR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('discount_percentage')
                    ->label(__('product.discount_percentage'))
                    ->numeric()
                    ->suffix('%')
                    ->sortable()
                    ->color(fn ($state) => $state > 0 ? 'success' : 'gray')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('discounted_price')
                    ->label(__('product.discounted_price'))
                    ->money('SAR')
                    ->sortable()
                    ->weight('bold')
                    ->color('success'),

                Tables\Columns\IconColumn::make('is_available')
                    ->label(__('field.is_available'))
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('preparation_time')
                    ->label(__('product.preparation_time'))
                    ->numeric()
                    ->suffix(' ' . __('common.minutes'))
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('sku')
                    ->label(__('product.sku'))
                    ->searchable()
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('calories')
                    ->label(__('product.calories'))
                    ->numeric()
                    ->suffix(' ' . __('common.calories'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('is_vegetarian')
                    ->label(__('product.is_vegetarian'))
                    ->boolean()
                    ->trueIcon('heroicon-o-leaf')
                    ->falseIcon('heroicon-o-x-mark')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('is_spicy')
                    ->label(__('product.is_spicy'))
                    ->boolean()
                    ->trueIcon('heroicon-o-fire')
                    ->falseIcon('heroicon-o-x-mark')
                    ->trueColor('danger')
                    ->falseColor('gray')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('is_featured')
                    ->label(__('product.is_featured'))
                    ->boolean()
                    ->trueIcon('heroicon-o-star')
                    ->falseIcon('heroicon-o-x-mark')
                    ->trueColor('warning')
                    ->falseColor('gray')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('total_orders')
                    ->label(__('product.orders_count'))
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color('info')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('average_rating')
                    ->label(__('product.rating'))
                    ->numeric(decimalPlaces: 1)
                    ->sortable()
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        $state >= 4.5 => 'success',
                        $state >= 3.5 => 'warning',
                        $state >= 2.5 => 'info',
                        default => 'danger',
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('stock_quantity')
                    ->label(__('product.stock'))
                    ->numeric()
                    ->sortable()
                    ->color(fn ($state) => match (true) {
                        $state > 10 => 'success',
                        $state > 5 => 'warning',
                        $state > 0 => 'danger',
                        default => 'gray',
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('field.created_at'))
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('merchant_id')
                    ->label(__('filter.merchant'))
                    ->relationship('merchant', 'business_name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('internal_category_id')
                    ->label(__('filter.category'))
                    ->relationship('internalCategory', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('food_nationality_id')
                    ->label(__('filter.nationality'))
                    ->relationship('foodNationality', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\TernaryFilter::make('is_available')
                    ->label(__('field.is_available'))
                    ->placeholder(__('filter.all'))
                    ->trueLabel(__('filter.available'))
                    ->falseLabel(__('filter.not_available')),
                Tables\Filters\TernaryFilter::make('is_featured')
                    ->label(__('field.is_featured'))
                    ->placeholder(__('filter.all'))
                    ->trueLabel(__('filter.featured'))
                    ->falseLabel(__('filter.not_featured')),
                Tables\Filters\TernaryFilter::make('is_vegetarian')
                    ->label(__('field.is_vegetarian'))
                    ->placeholder(__('filter.all'))
                    ->trueLabel(__('filter.vegetarian'))
                    ->falseLabel(__('filter.not_vegetarian')),
                Tables\Filters\Filter::make('price_range')
                    ->form([
                        Forms\Components\TextInput::make('price_from')
                            ->label(__('product.price_from'))
                            ->numeric()
                            ->prefix(__('common.sar')),
                        Forms\Components\TextInput::make('price_to')
                            ->label(__('product.price_to'))
                            ->numeric()
                            ->prefix(__('common.sar')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['price_from'],
                                fn (Builder $query, $price): Builder => $query->where('discounted_price', '>=', $price),
                            )
                            ->when(
                                $data['price_to'],
                                fn (Builder $query, $price): Builder => $query->where('discounted_price', '<=', $price),
                            );
                    })
                    ->label(__('product.price_range')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label(__('table.view')),
                Tables\Actions\EditAction::make()
                    ->label(__('table.edit')),
                Tables\Actions\Action::make('toggle_availability')
                    ->label(fn ($record) => $record->is_available ? __('product.hide') : __('product.show'))
                    ->icon(fn ($record) => $record->is_available ? 'heroicon-o-eye-slash' : 'heroicon-o-eye')
                    ->color(fn ($record) => $record->is_available ? 'warning' : 'success')
                    ->action(fn ($record) => $record->update(['is_available' => !$record->is_available])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label(__('table.delete')),
                    Tables\Actions\BulkAction::make('make_available')
                        ->label(__('product.make_available'))
                        ->icon('heroicon-o-eye')
                        ->color('success')
                        ->action(fn ($records) => $records->each->update(['is_available' => true])),
                    Tables\Actions\BulkAction::make('make_unavailable')
                        ->label(__('product.make_unavailable'))
                        ->icon('heroicon-o-eye-slash')
                        ->color('warning')
                        ->action(fn ($records) => $records->each->update(['is_available' => false])),
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
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'view' => Pages\ViewProduct::route('/{record}'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
