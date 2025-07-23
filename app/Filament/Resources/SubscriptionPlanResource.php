<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SubscriptionPlanResource\Pages;
use App\Filament\Resources\SubscriptionPlanResource\RelationManagers;
use App\Models\SubscriptionPlan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SubscriptionPlanResource extends Resource
{
    protected static ?string $model = SubscriptionPlan::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationGroup = null;

    protected static ?string $navigationLabel = null;

    protected static ?string $modelLabel = null;

    protected static ?string $pluralModelLabel = null;

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.system_settings');
    }

    public static function getNavigationLabel(): string
    {
        return __('subscription_plan.title');
    }

    public static function getModelLabel(): string
    {
        return __('subscription_plan.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('subscription_plan.title');
    }

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('subscription_plan.basic_information'))
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label(__('subscription_plan.name'))
                            ->required()
                            ->maxLength(191)
                            ->minLength(2)
                            ->rules(['required', 'string', 'min:2', 'max:191'])
                            ->validationMessages([
                                'required' => __('validation.required', ['attribute' => __('subscription_plan.name')]),
                                'min' => __('validation.min.string', ['attribute' => __('subscription_plan.name'), 'min' => 2]),
                                'max' => __('validation.max.string', ['attribute' => __('subscription_plan.name'), 'max' => 191]),
                            ]),
                        Forms\Components\Textarea::make('description')
                            ->label(__('subscription_plan.description'))
                            ->maxLength(1000)
                            ->rows(3)
                            ->rules(['nullable', 'string', 'max:1000'])
                            ->validationMessages([
                                'max' => __('validation.max.string', ['attribute' => __('subscription_plan.description'), 'max' => 1000]),
                            ]),
                        Forms\Components\TextInput::make('price')
                            ->label(__('subscription_plan.price'))
                            ->required()
                            ->numeric()
                            ->default(0.00)
                            ->prefix(__('currency.sar'))
                            ->minValue(0)
                            ->rules(['required', 'numeric', 'min:0'])
                            ->validationMessages([
                                'required' => __('validation.required', ['attribute' => __('subscription_plan.price')]),
                                'numeric' => __('validation.numeric', ['attribute' => __('subscription_plan.price')]),
                                'min' => __('validation.min.numeric', ['attribute' => __('subscription_plan.price'), 'min' => 0]),
                            ]),
                        Forms\Components\Select::make('period')
                            ->label(__('subscription_plan.period'))
                            ->required()
                            ->options([
                                'monthly' => __('subscription_plan.period_monthly'),
                                'yearly' => __('subscription_plan.period_yearly'),
                                'weekly' => __('subscription_plan.period_weekly'),
                                'daily' => __('subscription_plan.period_daily'),
                            ])
                            ->rules(['required', 'in:monthly,yearly,weekly,daily'])
                            ->validationMessages([
                                'required' => __('validation.required', ['attribute' => __('subscription_plan.period')]),
                                'in' => __('validation.in', ['attribute' => __('subscription_plan.period')]),
                            ]),
                        Forms\Components\Textarea::make('features')
                            ->label(__('subscription_plan.features'))
                            ->required()
                            ->maxLength(2000)
                            ->rows(4)
                            ->helperText(__('subscription_plan.features_helper'))
                            ->rules(['required', 'string', 'max:2000'])
                            ->validationMessages([
                                'required' => __('validation.required', ['attribute' => __('subscription_plan.features')]),
                                'max' => __('validation.max.string', ['attribute' => __('subscription_plan.features'), 'max' => 2000]),
                            ]),
                    ])->columns(2),
                Forms\Components\Section::make('إعدادات الخطة')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('نشط')
                            ->required()
                            ->default(true),
                        Forms\Components\Toggle::make('is_popular')
                            ->label('خطة شائعة')
                            ->required()
                            ->default(false)
                            ->helperText('ستظهر هذه الخطة كخطة مميزة'),
                        Forms\Components\TextInput::make('sort_order')
                            ->label(__('subscription.display_order'))
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->rules(['required', 'integer', 'min:0'])
                            ->validationMessages([
                                'required' => __('subscription.display_order_required'),
                                'integer' => __('subscription.display_order_integer'),
                                'min' => __('subscription.display_order_min'),
                            ]),
                        Forms\Components\TextInput::make('stripe_price_id')
                            ->label('معرف السعر في Stripe')
                            ->maxLength(191)
                            ->rules(['nullable', 'string', 'max:191'])
                            ->validationMessages([
                                'max' => 'معرف السعر لا يمكن أن يتجاوز 191 حرف',
                            ])
                            ->helperText('اتركه فارغاً إذا لم تكن تستخدم Stripe'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('price')
                    ->money()
                    ->sortable(),
                Tables\Columns\TextColumn::make('period'),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_popular')
                    ->boolean(),
                Tables\Columns\TextColumn::make('sort_order')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('stripe_price_id')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label(__('action.view'))
                    ->modal()
                    ->modalHeading(__('subscription.view_details'))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel(__('action.close')),
                Tables\Actions\EditAction::make()
                    ->label(__('action.edit'))
                    ->modal()
                    ->modalHeading(__('subscription.edit_plan'))
                    ->modalSubmitActionLabel(__('role.save_changes'))
                    ->modalCancelActionLabel(__('action.cancel')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
            'index' => Pages\ListSubscriptionPlans::route('/'),
            'create' => Pages\CreateSubscriptionPlan::route('/create'),
            'edit' => Pages\EditSubscriptionPlan::route('/{record}/edit'),
        ];
    }
}
