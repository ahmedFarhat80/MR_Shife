<?php

namespace App\Filament\Resources\AdminResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Services\AdminTranslationService;
use App\Services\AdminPerformanceService;
use App\Filament\Forms\Components\PermissionsMatrix;

class RolesRelationManager extends RelationManager
{
    protected static string $relationship = 'roles';

    protected static ?string $title = 'الأدوار والصلاحيات';

    protected static ?string $modelLabel = 'دور';

    protected static ?string $pluralModelLabel = 'الأدوار';

    public function isReadOnly(): bool
    {
        // جعل العلاقة للقراءة فقط للسوبر أدمن
        return $this->ownerRecord->email === 'admin@mrshife.com';
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات الدور')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('اسم الدور')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(191)
                            ->minLength(2)
                            ->validationMessages([
                                'required' => 'اسم الدور مطلوب',
                                'unique' => 'اسم الدور موجود بالفعل',
                                'min' => 'اسم الدور يجب أن يكون على الأقل حرفين',
                                'max' => 'اسم الدور لا يمكن أن يتجاوز 191 حرف',
                            ])
                            ->helperText('مثال: مدير عام، مدير محتوى، مدير عملاء'),

                        Forms\Components\Hidden::make('guard_name')
                            ->default('admin'),
                    ])->columns(1),

                Forms\Components\Section::make('الصلاحيات')
                    ->schema([
                        PermissionsMatrix::make('permissions')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        // إضافة رسالة تنبيه للسوبر أدمن
        if ($this->ownerRecord->email === 'admin@mrshife.com') {
            \Filament\Notifications\Notification::make()
                ->title('تنبيه')
                ->body('هذا المستخدم هو السوبر أدمن ولا يمكن تعديل صلاحياته')
                ->warning()
                ->persistent()
                ->send();
        }

        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('اسم الدور')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'super_admin' => 'سوبر أدمن',
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

                Tables\Columns\TextColumn::make('permissions_count')
                    ->label('عدد الصلاحيات')
                    ->counts('permissions')
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('permissions_preview')
                    ->label('الصلاحيات')
                    ->state(function ($record) {
                        $permissionsCount = $record->permissions->count();

                        if ($permissionsCount === 0) {
                            return 'لا توجد صلاحيات';
                        }

                        $permissions = $record->permissions->take(2)->pluck('name')->map(function ($name) {
                            return AdminTranslationService::translatePermissionName($name);
                        });

                        $preview = $permissions->join('، ');
                        $remaining = $permissionsCount - 2;

                        if ($remaining > 0) {
                            $preview .= " و {$remaining} أخرى";
                        }

                        return $preview;
                    })
                    ->html()
                    ->wrap()
                    ->tooltip(function ($record) {
                        $permissions = $record->permissions->pluck('name')->map(function ($name) {
                            return AdminTranslationService::translatePermissionName($name);
                        })->join('، ');
                        return $permissions ?: 'لا توجد صلاحيات';
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
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
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('إضافة دور جديد')
                    ->modalHeading('إضافة دور جديد')
                    ->modalSubmitActionLabel('إنشاء الدور')
                    ->modalCancelActionLabel('إلغاء')
                    ->visible(fn () => $this->ownerRecord->email !== 'admin@mrshife.com')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['guard_name'] = 'admin';
                        return $data;
                    })
                    ->after(function ($record, array $data) {
                        if (!empty($data['permissions'])) {
                            $record->syncPermissions($data['permissions']);
                        }

                        // تحديث الكاش فوراً
                        \App\Filament\Resources\AdminResource::clearAllCaches();

                        Notification::make()
                            ->title('تم إنشاء الدور بنجاح')
                            ->body("تم إنشاء الدور {$record->name} وتعيين الصلاحيات بنجاح")
                            ->success()
                            ->send();
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('view_permissions')
                    ->label('عرض الصلاحيات')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->modal()
                    ->modalHeading(fn ($record) => "صلاحيات الدور: {$record->name}")
                    ->modalContent(function ($record) {
                        return $this->getPermissionsModalContent($record);
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('إغلاق'),

                Tables\Actions\EditAction::make()
                    ->label('تعديل')
                    ->modalHeading('تعديل الدور')
                    ->modalSubmitActionLabel('حفظ التغييرات')
                    ->modalCancelActionLabel('إلغاء')
                    ->visible(fn () => $this->ownerRecord->email !== 'admin@mrshife.com')
                    ->fillForm(function ($record): array {
                        return [
                            'name' => $record->name,
                            'permissions' => $record->permissions->pluck('id')->toArray(),
                        ];
                    })
                    ->action(function ($record, array $data) {
                        // تحديث اسم الدور
                        $record->update(['name' => $data['name']]);

                        // تحديث الصلاحيات
                        if (isset($data['permissions'])) {
                            $record->syncPermissions($data['permissions']);
                        }

                        // تحديث الكاش فوراً
                        \App\Filament\Resources\AdminResource::clearAllCaches();

                        Notification::make()
                            ->title('تم تحديث الدور بنجاح')
                            ->body("تم تحديث الدور {$record->name} والصلاحيات بنجاح")
                            ->success()
                            ->send();
                    }),

                Tables\Actions\DeleteAction::make()
                    ->label('حذف')
                    ->requiresConfirmation()
                    ->modalHeading('حذف الدور')
                    ->modalDescription('هل أنت متأكد من حذف هذا الدور؟ لا يمكن التراجع عن هذا الإجراء.')
                    ->modalSubmitActionLabel('حذف')
                    ->modalCancelActionLabel('إلغاء')
                    ->visible(fn ($record) => $record && $record->name !== 'super_admin' && $this->ownerRecord->email !== 'admin@mrshife.com'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('حذف المحدد'),
                ]),
            ]);
    }



    private function getPermissionsModalContent($record)
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
