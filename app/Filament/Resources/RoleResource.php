<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RoleResource\Pages;
use App\Services\AdminTranslationService;
use App\Filament\Forms\Components\PermissionsMatrix;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static ?string $navigationIcon = 'heroicon-o-key';

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
        return __('role.title');
    }

    public static function getModelLabel(): string
    {
        return __('role.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('role.title');
    }

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('role.information'))
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label(__('role.name'))
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(191)
                            ->minLength(2)
                            ->validationMessages([
                                'required' => __('validation.required', ['attribute' => __('role.name')]),
                                'unique' => __('validation.unique', ['attribute' => __('role.name')]),
                                'min' => __('validation.min.string', ['attribute' => __('role.name'), 'min' => 2]),
                                'max' => __('validation.max.string', ['attribute' => __('role.name'), 'max' => 191]),
                            ])
                            ->helperText(__('role.name_examples')),

                        Forms\Components\Hidden::make('guard_name')
                            ->default('admin'),
                    ])->columns(1),

                Forms\Components\Section::make(__('role.permissions'))
                    ->schema([
                        PermissionsMatrix::make('permissions')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(
                Role::query()
                    ->select('id', 'name', 'guard_name', 'created_at')
                    ->with(['permissions:id,name'])
                    ->withCount('permissions')
            )
            ->defaultPaginationPageOption(10)
            ->deferLoading()
            ->poll(null)
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('role.name'))
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'super_admin' => __('role.super_admin'),
                        default => $state,
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'super_admin' => 'danger',
                        'مدير عام' => 'success',
                        'مدير محتوى' => 'info',
                        'مدير عملاء' => 'warning',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('permissions_preview')
                    ->label(__('role.permissions'))
                    ->state(function ($record) {
                        // استخدام العلاقة المحملة مسبقاً
                        $permissionsCount = $record->permissions_count ?? $record->permissions->count();

                        if ($permissionsCount === 0) {
                            return __('role.no_permissions');
                        }

                        // أخذ أول صلاحيتين فقط لتوفير الذاكرة
                        $permissions = $record->permissions->take(2)->pluck('name')->map(function ($name) {
                            return AdminTranslationService::translatePermissionName($name);
                        });

                        $preview = $permissions->join(__('common.comma_separator'));
                        $remaining = $permissionsCount - 2;

                        if ($remaining > 0) {
                            $preview .= ' ' . __('role.and_others', ['count' => $remaining]);
                        }

                        return $preview;
                    })
                    ->html()
                    ->wrap()
                    ->tooltip(function ($record) {
                        // تحميل الصلاحيات فقط عند الحاجة (tooltip)
                        if (!$record->relationLoaded('permissions')) {
                            $record->load('permissions:id,name');
                        }
                        $permissions = $record->permissions->pluck('name')->map(function ($name) {
                            return AdminTranslationService::translatePermissionName($name);
                        })->join(__('common.comma_separator'));
                        return $permissions ?: __('role.no_permissions');
                    }),

                Tables\Columns\TextColumn::make('permissions_count')
                    ->label(__('role.permissions_count'))
                    ->state(fn ($record) => $record->permissions_count ?? $record->permissions->count())
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('users_count')
                    ->label(__('role.users_count'))
                    ->state(function ($record) {
                        return \App\Models\Admin::role($record->name)->count();
                    })
                    ->badge()
                    ->color('success'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('field.created_at'))
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('guard_name')
                    ->label('نوع الحارس')
                    ->options([
                        'admin' => 'المديرين',
                        'web' => 'المستخدمين',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('view_permissions')
                    ->label(__('role.view_permissions'))
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->modal()
                    ->modalHeading(fn ($record) => __('role.permissions_for_role', ['role' => $record->name]))
                    ->modalContent(function ($record) {
                        return self::getPermissionsModalContent($record);
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel(__('action.close')),

                Tables\Actions\Action::make('copy_permissions')
                    ->label(__('role.copy_permissions'))
                    ->icon('heroicon-o-document-duplicate')
                    ->color('info')
                    ->form([
                        Forms\Components\Select::make('source_role_id')
                            ->label(__('role.copy_permissions_from'))
                            ->options(function ($record) {
                                return Role::where('guard_name', 'admin')
                                    ->where('id', '!=', $record->id)
                                    ->pluck('name', 'id')
                                    ->toArray();
                            })
                            ->required()
                            ->searchable()
                            ->helperText(__('role.select_source_role')),
                    ])
                    ->action(function ($record, array $data) {
                        $sourceRole = Role::find($data['source_role_id']);
                        if ($sourceRole) {
                            $record->syncPermissions($sourceRole->permissions);

                            Notification::make()
                                ->title(__('role.permissions_copied_success'))
                                ->body(__('role.permissions_copied_desc', [
                                    'source' => $sourceRole->name,
                                    'target' => $record->name
                                ]))
                                ->success()
                                ->send();
                        }
                    })
                    ->requiresConfirmation()
                    ->modalHeading(__('role.copy_permissions_modal_title'))
                    ->modalDescription(__('role.copy_permissions_modal_desc'))
                    ->modalSubmitActionLabel(__('role.copy_permissions_action'))
                    ->modalCancelActionLabel(__('action.cancel')),

                Tables\Actions\EditAction::make()
                    ->label(__('action.edit'))
                    ->modal()
                    ->modalHeading(__('role.edit_role'))
                    ->modalSubmitActionLabel(__('role.save_changes'))
                    ->modalCancelActionLabel(__('action.cancel')),

                Tables\Actions\DeleteAction::make()
                    ->label(__('action.delete'))
                    ->requiresConfirmation()
                    ->modalHeading(__('role.delete_role'))
                    ->modalDescription(__('role.delete_role_confirm'))
                    ->modalSubmitActionLabel(__('action.delete'))
                    ->modalCancelActionLabel(__('action.cancel'))
                    ->visible(fn ($record) => $record && $record->name !== 'super_admin'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label(__('role.delete_selected')),
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
            'index' => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'edit' => Pages\EditRole::route('/{record}/edit'),
        ];
    }



    public static function getPermissionsModalContent($record)
    {
        $permissions = $record->permissions->groupBy(function ($permission) {
            $parts = explode('_', $permission->name);
            return $parts[count($parts) - 1] ?? 'other';
        });

        $content = '<div class="space-y-4">';

        foreach ($permissions as $group => $groupPermissions) {
            $groupName = AdminTranslationService::getPermissionGroupName($group);

            $content .= "<div class='p-3 rounded-lg bg-gray-50'>";
            $content .= "<h4 class='mb-2 font-semibold text-gray-900'>{$groupName}</h4>";
            $content .= "<div class='flex flex-wrap gap-1'>";

            foreach ($groupPermissions as $permission) {
                $translatedName = AdminTranslationService::translatePermissionName($permission->name);
                $content .= "<span class='inline-flex items-center px-2 py-1 text-xs font-medium text-blue-800 bg-blue-100 rounded-full'>{$translatedName}</span>";
            }

            $content .= "</div></div>";
        }

        $content .= '</div>';

        return new \Illuminate\Support\HtmlString($content);
    }




}
