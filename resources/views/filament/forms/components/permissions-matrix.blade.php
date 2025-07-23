@php
$groups = $getPermissionGroups();
$record = $getCurrentRecord();

// Get selected permissions from the record or state
if ($record && $record->exists) {
$selectedPermissions = $record->permissions->pluck('id')->toArray();
} else {
$selectedPermissions = collect($getState() ?? [])->map(fn($item) => is_array($item) ? $item['id'] : $item)->toArray();
}
@endphp

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div class="permissions-matrix">
        <!-- Header -->
        <div class="mb-6">
            <h3 class="mb-2 text-lg font-semibold text-gray-900 dark:text-white">
                إدارة الصلاحيات
            </h3>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                اختر الصلاحيات المناسبة لهذا الدور. يمكنك تحديد صلاحيات محددة لكل مورد.
            </p>
        </div>

        <!-- Quick Actions -->
        <div
            class="flex flex-wrap items-center justify-between gap-4 p-4 mb-6 border border-gray-200 rounded-lg bg-gray-50">
            <div class="flex flex-wrap gap-3">
                <button type="button" onclick="selectAllPermissions()"
                    class="flex items-center px-4 py-2 text-sm font-semibold transition-colors border rounded-lg bg-amber-600 border-amber-600 hover:bg-amber-700">
                    <svg class="w-4 h-4 ml-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                            clip-rule="evenodd"></path>
                    </svg>
                    تحديد الكل
                </button>
                <button type="button" onclick="deselectAllPermissions()"
                    class="flex items-center px-4 py-2 text-sm font-semibold text-gray-700 transition-colors bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                    <svg class="w-4 h-4 ml-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                            clip-rule="evenodd"></path>
                    </svg>
                    إلغاء التحديد
                </button>
            </div>
            <div class="flex items-center px-3 py-2 text-sm text-gray-600 bg-white border border-gray-200 rounded-lg">
                <span class="ml-2 font-medium">المحدد:</span>
                <span id="selectedCount"
                    class="px-2 py-1 mx-1 text-xs font-bold text-white rounded-full bg-amber-600">0</span>
                <span class="mx-1">من</span>
                <span id="totalCount" class="font-semibold text-gray-800">0</span>
            </div>
        </div>

        <!-- Permissions Grid -->
        <div class="space-y-6">
            @foreach($groups as $resourceKey => $group)
            <div class="overflow-hidden bg-white border border-gray-200 rounded-lg shadow-sm">
                <!-- Group Header -->
                <div class="px-4 py-3 border-b border-gray-200 bg-gray-50">
                    <div class="flex items-center justify-between">
                        <h4 class="text-base font-semibold text-gray-900">
                            {{ $group['name'] }}
                        </h4>
                        <div class="flex gap-2">
                            <button type="button" onclick="selectGroupPermissions('{{ $resourceKey }}')"
                                class="px-3 py-1.5 text-xs font-medium text-amber-700 bg-amber-50 border border-amber-200 rounded-md hover:bg-amber-100 transition-colors">
                                تحديد الكل
                            </button>
                            <button type="button" onclick="deselectGroupPermissions('{{ $resourceKey }}')"
                                class="px-3 py-1.5 text-xs font-medium text-gray-700 bg-gray-50 border border-gray-200 rounded-md hover:bg-gray-100 transition-colors">
                                إلغاء التحديد
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Permissions Grid -->
                <div class="p-4">
                    <div class="grid grid-cols-2 gap-3 md:grid-cols-3 lg:grid-cols-4">
                        @foreach($group['permissions'] as $action => $permission)
                        <div class="permission-item">
                            <label
                                class="relative flex items-center p-3 overflow-hidden transition-all duration-200 border border-gray-200 rounded-lg cursor-pointer hover:bg-amber-50 hover:border-amber-300 group">
                                <input type="checkbox" name="{{ $getStatePath() }}[]" value="{{ $permission['id'] }}"
                                    @if(in_array($permission['id'], $selectedPermissions)) checked @endif
                                    onchange="updatePermissionCount(); updateLivewireState();"
                                    class="flex-shrink-0 w-4 h-4 mr-3 transition-colors bg-gray-100 border-gray-300 rounded text-amber-600 focus:ring-amber-500 focus:ring-2"
                                    data-group="{{ $resourceKey }}">
                                <span
                                    class="text-sm font-medium text-gray-700 transition-colors group-hover:text-amber-700">
                                    &nbsp; {{ $permission['label'] }}  &nbsp;
                                </span>
                            </label>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <script>
        function selectAllPermissions() {
            document.querySelectorAll('.permissions-matrix input[type="checkbox"]').forEach(checkbox => {
                checkbox.checked = true;
            });
            updatePermissionCount();
            updateLivewireState();
        }

        function deselectAllPermissions() {
            document.querySelectorAll('.permissions-matrix input[type="checkbox"]').forEach(checkbox => {
                checkbox.checked = false;
            });
            updatePermissionCount();
            updateLivewireState();
        }

        function selectGroupPermissions(group) {
            document.querySelectorAll(`.permissions-matrix input[data-group="${group}"]`).forEach(checkbox => {
                checkbox.checked = true;
            });
            updatePermissionCount();
            updateLivewireState();
        }

        function deselectGroupPermissions(group) {
            document.querySelectorAll(`.permissions-matrix input[data-group="${group}"]`).forEach(checkbox => {
                checkbox.checked = false;
            });
            updatePermissionCount();
            updateLivewireState();
        }

        function updatePermissionCount() {
            const selectedCount = document.querySelectorAll('.permissions-matrix input[type="checkbox"]:checked').length;
            const totalCount = document.querySelectorAll('.permissions-matrix input[type="checkbox"]').length;

            const selectedCountElement = document.getElementById('selectedCount');
            const totalCountElement = document.getElementById('totalCount');

            if (selectedCountElement) selectedCountElement.textContent = selectedCount;
            if (totalCountElement) totalCountElement.textContent = totalCount;
        }

        function updateLivewireState() {
            const selectedValues = Array.from(document.querySelectorAll('.permissions-matrix input[type="checkbox"]:checked')).map(cb => parseInt(cb.value));

            // تحديث فوري للحالة مع إجبار التحديث
            @this.set('{{ $getStatePath() }}', selectedValues).then(() => {
                console.log('Livewire state updated successfully:', selectedValues);
            }).catch((error) => {
                console.error('Error updating Livewire state:', error);
            });

            // تحديث إضافي للتأكد
            setTimeout(() => {
                @this.set('{{ $getStatePath() }}', selectedValues);
            }, 100);

            // تسجيل للتأكد من التحديث
            console.log('Permissions being updated:', selectedValues);
        }

        // Debounce function لتجنب التحديث المتكرر
        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }

        // Initialize count on page load
        document.addEventListener('DOMContentLoaded', function() {
            updatePermissionCount();

            // إنشاء دالة debounced للتحديث
            const debouncedUpdate = debounce(updateLivewireState, 300);

            // Add event listeners to all checkboxes
            document.querySelectorAll('.permissions-matrix input[type="checkbox"]').forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    updatePermissionCount();
                    // تحديث فوري
                    updateLivewireState();
                    // تحديث إضافي مع debounce
                    debouncedUpdate();
                });
            });
        });
    </script>

    <style>
        .permissions-matrix {
            direction: rtl;
        }

        .permissions-matrix .grid {
            direction: ltr;
        }

        .permission-item {
            direction: rtl;
        }

        .permission-item label {
            width: 100%;
        }

        /* إصلاح ألوان الصلاحيات المحددة */
        .permission-item input:checked+span {
            color: #d97706 !important;
            font-weight: 700 !important;
            position: relative;
            z-index: 3;
        }

        .permission-item input:checked {
            background-color: #f59e0b !important;
            border-color: #f59e0b !important;
            position: relative;
            z-index: 4;
        }

        .permission-item label:has(input:checked) {
            background: linear-gradient(135deg, #fef3c7 0%, #fed7aa 50%, #fef3c7 100%) !important;
            border-color: #f59e0b !important;
            box-shadow: 0 4px 15px rgba(245, 158, 11, 0.3), inset 0 1px 0 rgba(255, 255, 255, 0.6) !important;
            transform: translateY(-1px) !important;
        }

        /* تحسين hover للصلاحيات */
        .permission-item label:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        /* إصلاح المساحات */
        .permission-item input[type="checkbox"] {
            margin-left: 0 !important;
            margin-right: 12px !important;
        }

        .permission-item span {
            margin-right: 0 !important;
            margin-left: 0 !important;
            padding-right: 0 !important;
            flex: 1;
        }

        /* التأثير الزجاجي الجميل */
        .permission-item label::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(245, 158, 11, 0.2), transparent);
            transition: left 0.6s ease;
        }

        .permission-item label:hover::before {
            left: 100%;
        }

        /* تأثير الانعكاس */
        .permission-item label::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(255,255,255,0.1) 0%, transparent 50%, rgba(245,158,11,0.05) 100%);
            opacity: 0;
            transition: opacity 0.3s ease;
            pointer-events: none;
        }

        .permission-item label:hover::after {
            opacity: 1;
        }

        /* تأثير زجاجي للعناصر المحددة */
        .permission-item label:has(input:checked)::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, rgba(255,255,255,0.3) 0%, transparent 30%, rgba(245,158,11,0.1) 70%, rgba(255,255,255,0.2) 100%);
            pointer-events: none;
            z-index: 1;
        }

        .permission-item label:has(input:checked)::after {
            content: '';
            position: absolute;
            top: 2px;
            left: 2px;
            right: 2px;
            bottom: 2px;
            background: linear-gradient(135deg, rgba(255,255,255,0.4) 0%, transparent 50%, rgba(245,158,11,0.2) 100%);
            border-radius: 6px;
            pointer-events: none;
            z-index: 2;
        }

        /* تأثير النبضة للعناصر المحددة */
        .permission-item label:has(input:checked) {
            animation: gentle-pulse 3s ease-in-out infinite;
        }

        @keyframes gentle-pulse {
            0%, 100% {
                box-shadow: 0 4px 15px rgba(245, 158, 11, 0.3), inset 0 1px 0 rgba(255, 255, 255, 0.6);
            }
            50% {
                box-shadow: 0 6px 20px rgba(245, 158, 11, 0.4), inset 0 1px 0 rgba(255, 255, 255, 0.8);
            }
        }

        /* تحسين الاستجابة */
        @media (max-width: 768px) {
            .permissions-matrix .grid {
                grid-template-columns: 1fr !important;
            }

            .permission-item label {
                padding: 12px;
            }
        }
    </style>
</x-dynamic-component>
