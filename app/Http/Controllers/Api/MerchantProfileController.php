<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MerchantResource;
use App\Models\Merchant;
use App\Services\ApiResponseService;
use App\Helpers\ImageHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class MerchantProfileController extends Controller
{
    protected ApiResponseService $apiResponse;

    public function __construct(ApiResponseService $apiResponse)
    {
        $this->apiResponse = $apiResponse;
    }

    /**
     * Get merchant profile
     */
    public function profile(Request $request)
    {
        return $this->apiResponse->success(
            __('api.profile_retrieved'),
            [
                'merchant' => new MerchantResource($request->user()),
            ]
        );
    }

    /**
     * Update basic merchant information
     */
    public function updateBasicInfo(Request $request)
    {
        $merchant = $request->user();

        $validator = Validator::make($request->all(), [
            'name_en' => 'nullable|string|max:255',
            'name_ar' => 'nullable|string|max:255',
            'email' => 'nullable|email|unique:merchants,email,' . $merchant->id,
            'preferred_language' => 'nullable|string|in:ar,en',
        ]);

        if ($validator->fails()) {
            return $this->apiResponse->validationError($validator->errors());
        }

        try {
            $updateData = [];

            // Handle name updates
            if ($request->has('name_en') || $request->has('name_ar')) {
                $updateData['name'] = [
                    'en' => $request->name_en ?? $merchant->getTranslation('name', 'en'),
                    'ar' => $request->name_ar ?? $merchant->getTranslation('name', 'ar'),
                ];
            }

            // Handle other fields
            $fields = ['email', 'preferred_language'];
            foreach ($fields as $field) {
                if ($request->has($field)) {
                    $updateData[$field] = $request->$field;
                }
            }

            $merchant->update($updateData);

            return $this->apiResponse->success(
                __('merchant.basic_info_updated_successfully'),
                [
                    'merchant' => new MerchantResource($merchant->fresh()),
                ]
            );
        } catch (\Exception $e) {
            return $this->apiResponse->error(__('merchant.basic_info_update_failed') . ': ' . $e->getMessage());
        }
    }

    /**
     * Update business information
     */
    public function updateBusinessInfo(Request $request)
    {
        $merchant = $request->user();

        $validator = Validator::make($request->all(), [
            'business_name_en' => 'nullable|string|max:255',
            'business_name_ar' => 'nullable|string|max:255',
            'business_address_en' => 'nullable|string',
            'business_address_ar' => 'nullable|string',
            'business_type' => 'nullable|string|max:100',
            'commercial_registration_number' => 'nullable|string|max:100',
            'work_permit' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'id_or_passport' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'health_certificate' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        if ($validator->fails()) {
            return $this->apiResponse->validationError($validator->errors());
        }

        try {
            DB::beginTransaction();

            $updateData = [];

            // Handle business name updates
            if ($request->has('business_name_en') || $request->has('business_name_ar')) {
                $updateData['business_name'] = [
                    'en' => $request->business_name_en ?? $merchant->getTranslation('business_name', 'en'),
                    'ar' => $request->business_name_ar ?? $merchant->getTranslation('business_name', 'ar'),
                ];
            }

            // Handle business address updates
            if ($request->has('business_address_en') || $request->has('business_address_ar')) {
                $updateData['business_address'] = [
                    'en' => $request->business_address_en ?? $merchant->getTranslation('business_address', 'en'),
                    'ar' => $request->business_address_ar ?? $merchant->getTranslation('business_address', 'ar'),
                ];
            }

            // Handle file uploads
            $fileFields = ['work_permit', 'id_or_passport', 'health_certificate'];
            foreach ($fileFields as $field) {
                if ($request->hasFile($field)) {
                    $uploadResult = ImageHelper::uploadSingle(
                        $request->file($field),
                        'merchant_documents',
                        'public',
                        $merchant->{$field}
                    );

                    if ($uploadResult['success']) {
                        $updateData[$field] = $uploadResult['path'];
                    } else {
                        throw new \Exception("Failed to upload {$field}: " . $uploadResult['message']);
                    }
                }
            }

            // Handle other fields
            $fields = ['business_type', 'commercial_registration_number'];
            foreach ($fields as $field) {
                if ($request->has($field)) {
                    $updateData[$field] = $request->$field;
                }
            }

            $merchant->update($updateData);

            DB::commit();

            return $this->apiResponse->success(
                __('merchant.business_info_updated_successfully'),
                [
                    'merchant' => new MerchantResource($merchant->fresh()),
                ]
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->apiResponse->error(__('merchant.business_info_update_failed') . ': ' . $e->getMessage());
        }
    }

    /**
     * Update business profile
     */
    public function updateBusinessProfile(Request $request)
    {
        $merchant = $request->user();

        // Parse JSON strings to arrays if needed
        $data = $request->all();

        // Handle business_hours JSON string
        if (isset($data['business_hours']) && is_string($data['business_hours'])) {
            $businessHours = json_decode($data['business_hours'], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $data['business_hours'] = $businessHours;
            }
        }



        $validator = Validator::make($data, [
            'business_logo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'business_description_en' => 'nullable|string|max:1000',
            'business_description_ar' => 'nullable|string|max:1000',
            'business_hours' => 'nullable|array',
            'business_phone' => 'nullable|string|max:20',
            'business_email' => 'nullable|email',
        ]);

        if ($validator->fails()) {
            return $this->apiResponse->validationError($validator->errors());
        }

        try {
            DB::beginTransaction();

            $updateData = [];

            // Handle business description updates
            if ($request->has('business_description_en') || $request->has('business_description_ar')) {
                $updateData['business_description'] = [
                    'en' => $request->business_description_en ?? $merchant->getTranslation('business_description', 'en'),
                    'ar' => $request->business_description_ar ?? $merchant->getTranslation('business_description', 'ar'),
                ];
            }

            // Handle logo upload
            if ($request->hasFile('business_logo')) {
                $uploadResult = ImageHelper::uploadWithResize(
                    $request->file('business_logo'),
                    'merchant_logos',
                    'logo',
                    'public',
                    $merchant->business_logo
                );

                if ($uploadResult['success']) {
                    $updateData['business_logo'] = $uploadResult['main_path'];
                } else {
                    throw new \Exception("Failed to upload business logo: " . $uploadResult['message']);
                }
            }

            // Handle other fields
            $fields = ['business_hours', 'business_phone', 'business_email'];
            foreach ($fields as $field) {
                if (isset($data[$field])) {
                    $updateData[$field] = $data[$field];
                }
            }

            $merchant->update($updateData);

            DB::commit();

            return $this->apiResponse->success(
                __('merchant.business_profile_updated_successfully'),
                [
                    'merchant' => new MerchantResource($merchant->fresh()),
                ]
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->apiResponse->error(__('merchant.business_profile_update_failed') . ': ' . $e->getMessage());
        }
    }

    /**
     * Update location information
     */
    public function updateLocation(Request $request)
    {
        $merchant = $request->user();

        $validator = Validator::make($request->all(), [
            'location_latitude' => 'nullable|numeric|between:-90,90',
            'location_longitude' => 'nullable|numeric|between:-180,180',
            'location_address_en' => 'nullable|string',
            'location_address_ar' => 'nullable|string',
            'location_city' => 'nullable|string|max:100',
            'location_area' => 'nullable|string|max:100',
            'location_building' => 'nullable|string|max:100',
            'location_floor' => 'nullable|string|max:50',
            'location_notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return $this->apiResponse->validationError($validator->errors());
        }

        try {
            $updateData = [];

            // Handle location address updates
            if ($request->has('location_address_en') || $request->has('location_address_ar')) {
                $updateData['location_address'] = [
                    'en' => $request->location_address_en ?? $merchant->getTranslation('location_address', 'en'),
                    'ar' => $request->location_address_ar ?? $merchant->getTranslation('location_address', 'ar'),
                ];
            }

            // Handle other fields
            $fields = ['location_latitude', 'location_longitude', 'location_city', 'location_area',
                      'location_building', 'location_floor', 'location_notes'];

            foreach ($fields as $field) {
                if ($request->has($field)) {
                    $updateData[$field] = $request->$field;
                }
            }

            $merchant->update($updateData);

            return $this->apiResponse->success(
                __('merchant.location_updated_successfully'),
                [
                    'merchant' => new MerchantResource($merchant->fresh()),
                ]
            );
        } catch (\Exception $e) {
            return $this->apiResponse->error(__('merchant.location_update_failed') . ': ' . $e->getMessage());
        }
    }



    /**
     * Update notification settings
     */
    public function updateNotificationSettings(Request $request)
    {
        $merchant = $request->user();

        $validator = Validator::make($request->all(), [
            'email_notifications' => 'nullable|boolean',
            'sms_notifications' => 'nullable|boolean',
            'push_notifications' => 'nullable|boolean',
            'order_notifications' => 'nullable|boolean',
            'marketing_notifications' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->apiResponse->validationError($validator->errors());
        }

        try {
            $settings = $merchant->settings ?? [];

            $notificationFields = [
                'email_notifications', 'sms_notifications', 'push_notifications',
                'order_notifications', 'marketing_notifications'
            ];

            foreach ($notificationFields as $field) {
                if ($request->has($field)) {
                    $settings['notifications'][$field] = $request->boolean($field);
                }
            }

            $merchant->update(['settings' => $settings]);

            return $this->apiResponse->success(
                __('merchant.notification_settings_updated'),
                [
                    'settings' => $settings,
                ]
            );
        } catch (\Exception $e) {
            return $this->apiResponse->error(__('merchant.notification_settings_update_failed') . ': ' . $e->getMessage());
        }
    }

    /**
     * Get merchant statistics
     */
    public function statistics(Request $request)
    {
        $merchant = $request->user();

        // Calculate basic statistics
        $totalOrders = $merchant->orders()->count();
        $completedOrders = $merchant->orders()->where('status', 'delivered')->count();
        $pendingOrders = $merchant->orders()->whereIn('status', ['pending', 'confirmed', 'preparing'])->count();
        $totalRevenue = $merchant->orders()->where('status', 'delivered')->sum('total_amount');
        $averageOrderValue = $completedOrders > 0 ? $totalRevenue / $completedOrders : 0;

        // Recent activity (last 30 days)
        $recentOrders = $merchant->orders()->where('created_at', '>=', now()->subDays(30))->count();
        $recentRevenue = $merchant->orders()
            ->where('status', 'delivered')
            ->where('created_at', '>=', now()->subDays(30))
            ->sum('total_amount');

        $statistics = [
            'orders' => [
                'total' => $totalOrders,
                'completed' => $completedOrders,
                'pending' => $pendingOrders,
                'recent_30_days' => $recentOrders,
            ],
            'revenue' => [
                'total' => $totalRevenue,
                'average_order_value' => $averageOrderValue,
                'recent_30_days' => $recentRevenue,
            ],
            'account' => [
                'registration_date' => $merchant->created_at->format('Y-m-d'),
                'account_age_days' => $merchant->created_at->diffInDays(now()),
                'subscription_status' => $merchant->subscription_status,
                'is_verified' => $merchant->is_verified,
            ],
        ];

        return $this->apiResponse->success(
            __('merchant.statistics_retrieved'),
            $statistics
        );
    }

    /**
     * Get merchant dashboard data
     */
    public function dashboard(Request $request)
    {
        $merchant = $request->user();

        // Recent orders (last 10)
        $recentOrders = $merchant->orders()
            ->with(['customer', 'items'])
            ->latest()
            ->take(10)
            ->get();

        // Today's statistics
        $todayOrders = $merchant->orders()->whereDate('created_at', today())->count();
        $todayRevenue = $merchant->orders()
            ->where('status', 'delivered')
            ->whereDate('created_at', today())
            ->sum('total_amount');

        // This week's statistics
        $weekOrders = $merchant->orders()->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count();
        $weekRevenue = $merchant->orders()
            ->where('status', 'delivered')
            ->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->sum('total_amount');

        $dashboard = [
            'today' => [
                'orders' => $todayOrders,
                'revenue' => $todayRevenue,
            ],
            'this_week' => [
                'orders' => $weekOrders,
                'revenue' => $weekRevenue,
            ],
            'recent_orders' => $recentOrders,
            'merchant_info' => [
                'registration_completed' => $merchant->registration_step === 'completed',
                'subscription_active' => $merchant->subscription_status === 'active',
                'profile_completion' => $this->calculateProfileCompletion($merchant),
            ],
        ];

        return $this->apiResponse->success(
            __('merchant.dashboard_retrieved'),
            $dashboard
        );
    }

    /**
     * Calculate profile completion percentage
     */
    private function calculateProfileCompletion(Merchant $merchant): int
    {
        $fields = [
            'name' => !empty($merchant->name),
            'phone_number' => !empty($merchant->phone_number),
            'email' => !empty($merchant->email),
            'business_name' => !empty($merchant->business_name),
            'business_type' => !empty($merchant->business_type),
            'business_description' => !empty($merchant->business_description),
            'business_logo' => !empty($merchant->business_logo),
            'location_latitude' => !empty($merchant->location_latitude),
            'location_longitude' => !empty($merchant->location_longitude),
            'business_hours' => !empty($merchant->business_hours),
        ];

        $completedFields = array_filter($fields);
        return round((count($completedFields) / count($fields)) * 100);
    }
}
