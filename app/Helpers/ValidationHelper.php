<?php

namespace App\Helpers;

class ValidationHelper
{
    /**
     * Get translated validation messages for common fields.
     *
     * @param array $fields
     * @return array
     */
    public static function getTranslatedMessages(array $fields): array
    {
        $messages = [];

        foreach ($fields as $field) {
            $attribute = __("attributes.{$field}");
            
            $messages["{$field}.required"] = __('validation.required', ['attribute' => $attribute]);
            $messages["{$field}.string"] = __('validation.string', ['attribute' => $attribute]);
            $messages["{$field}.email"] = __('validation.email', ['attribute' => $attribute]);
            $messages["{$field}.unique"] = __('validation.unique', ['attribute' => $attribute]);
            $messages["{$field}.max"] = __('validation.max', ['attribute' => $attribute, 'max' => ':max']);
            $messages["{$field}.min"] = __('validation.min', ['attribute' => $attribute, 'min' => ':min']);
            $messages["{$field}.size"] = __('validation.size', ['attribute' => $attribute, 'size' => ':size']);
            $messages["{$field}.regex"] = __('validation.regex', ['attribute' => $attribute]);
            $messages["{$field}.in"] = __('validation.in', ['attribute' => $attribute]);
            $messages["{$field}.numeric"] = __('validation.numeric', ['attribute' => $attribute]);
            $messages["{$field}.between"] = __('validation.between', ['attribute' => $attribute, 'min' => ':min', 'max' => ':max']);
            $messages["{$field}.confirmed"] = __('validation.confirmed', ['attribute' => $attribute]);
            $messages["{$field}.required_if"] = __('validation.required_if', ['attribute' => $attribute, 'other' => ':other', 'value' => ':value']);
        }

        return $messages;
    }

    /**
     * Get translated attributes for fields.
     *
     * @param array $fields
     * @return array
     */
    public static function getTranslatedAttributes(array $fields): array
    {
        $attributes = [];

        foreach ($fields as $field) {
            $attributes[$field] = __("attributes.{$field}");
        }

        return $attributes;
    }

    /**
     * Get validation rules and messages for service provider registration.
     *
     * @return array
     */
    public static function getServiceProviderRegistrationValidation(): array
    {
        $fields = [
            'name_en', 'name_ar', 'phone_number', 'email', 'password',
            'business_name_en', 'business_name_ar', 'business_address_en', 
            'business_address_ar', 'business_type', 'subscription_plan', 
            'subscription_period', 'preferred_language'
        ];

        return [
            'rules' => [
                'name_en' => 'required|string|max:255',
                'name_ar' => 'nullable|string|max:255',
                            'phone_number' => 'required|string|unique:merchants,phone_number',
            'email' => 'nullable|email|unique:merchants,email',
                'password' => 'required|string|min:8|confirmed',
                'business_name_en' => 'required|string|max:255',
                'business_name_ar' => 'nullable|string|max:255',
                'business_address_en' => 'nullable|string',
                'business_address_ar' => 'nullable|string',
                'business_type' => 'required|string|in:restaurant,grocery,pharmacy,electronics,clothing,bakery,cafe,fast_food,other',
                'subscription_plan' => 'nullable|string|in:free,premium',
                'subscription_period' => 'nullable|string|in:monthly,half_year,annual',
                'preferred_language' => 'nullable|string|in:en,ar',
            ],
            'messages' => self::getTranslatedMessages($fields),
            'attributes' => self::getTranslatedAttributes($fields),
        ];
    }

    /**
     * Get validation rules and messages for customer registration.
     *
     * @return array
     */
    public static function getCustomerRegistrationValidation(): array
    {
        $fields = [
            'name_en', 'name_ar', 'phone_number', 'email', 'password',
            'address_en', 'address_ar', 'preferred_language'
        ];

        return [
            'rules' => [
                'name_en' => 'required|string|max:255',
                'name_ar' => 'nullable|string|max:255',
                'phone_number' => 'required|string|unique:customers,phone_number',
                'email' => 'nullable|email|unique:customers,email',
                'password' => 'required|string|min:8|confirmed',
                'address_en' => 'nullable|string',
                'address_ar' => 'nullable|string',
                'preferred_language' => 'nullable|string|in:en,ar',
            ],
            'messages' => self::getTranslatedMessages($fields),
            'attributes' => self::getTranslatedAttributes($fields),
        ];
    }

    /**
     * Get validation rules and messages for OTP verification.
     *
     * @return array
     */
    public static function getOTPValidation(): array
    {
        $fields = ['phone_number', 'code'];

        return [
            'rules' => [
                'phone_number' => 'required|string',
                'code' => 'required|string|size:6|regex:/^[0-9]{6}$/',
            ],
            'messages' => self::getTranslatedMessages($fields),
            'attributes' => self::getTranslatedAttributes($fields),
        ];
    }

    /**
     * Get validation rules and messages for business info.
     *
     * @return array
     */
    public static function getBusinessInfoValidation(): array
    {
        $fields = [
            'business_name_en', 'business_name_ar', 'business_address_en',
            'business_address_ar', 'business_type', 'location_latitude', 'location_longitude'
        ];

        return [
            'rules' => [
                'business_name_en' => 'required|string|max:255',
                'business_name_ar' => 'nullable|string|max:255',
                'business_address_en' => 'nullable|string',
                'business_address_ar' => 'nullable|string',
                'business_type' => 'required|string|in:restaurant,grocery,pharmacy,electronics,clothing,bakery,cafe,fast_food,other',
                'location_latitude' => 'nullable|numeric|between:-90,90',
                'location_longitude' => 'nullable|numeric|between:-180,180',
            ],
            'messages' => self::getTranslatedMessages($fields),
            'attributes' => self::getTranslatedAttributes($fields),
        ];
    }

    /**
     * Get validation rules and messages for subscription info.
     *
     * @return array
     */
    public static function getSubscriptionValidation(): array
    {
        $fields = ['subscription_plan', 'subscription_period'];

        return [
            'rules' => [
                'subscription_plan' => 'required|string|in:free,premium',
                'subscription_period' => 'required_if:subscription_plan,premium|nullable|string|in:monthly,half_year,annual',
            ],
            'messages' => self::getTranslatedMessages($fields),
            'attributes' => self::getTranslatedAttributes($fields),
        ];
    }
}
