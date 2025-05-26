<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MerchantRegistrationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name_en' => 'required|string|max:255',
            'name_ar' => 'nullable|string|max:255',
            'phone_number' => 'required|string|unique:merchants,phone_number|regex:/^[+]?[0-9\s\-\(\)]+$/',
            'email' => 'nullable|email|unique:merchants,email|max:255',
            'password' => 'required|string|min:8|confirmed|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/',
            'business_name_en' => 'required|string|max:255',
            'business_name_ar' => 'nullable|string|max:255',
            'business_address_en' => 'nullable|string|max:500',
            'business_address_ar' => 'nullable|string|max:500',
            'business_type' => 'required|string|max:100|in:restaurant,grocery,pharmacy,electronics,clothing,other',
            'subscription_plan' => 'nullable|string|in:free,premium',
            'subscription_period' => 'nullable|string|in:monthly,half_year,annual',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name_en.required' => 'The English name is required.',
            'phone_number.required' => 'The phone number is required.',
            'phone_number.unique' => 'This phone number is already registered.',
            'phone_number.regex' => 'Please enter a valid phone number.',
            'email.unique' => 'This email is already registered.',
            'password.required' => 'Password is required.',
            'password.min' => 'Password must be at least 8 characters.',
            'password.confirmed' => 'Password confirmation does not match.',
            'password.regex' => 'Password must contain at least one uppercase letter, one lowercase letter, and one number.',
            'business_name_en.required' => 'The English business name is required.',
            'business_type.required' => 'The business type is required.',
            'business_type.in' => 'Please select a valid business type.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name_en' => __('attributes.name_en'),
            'name_ar' => __('attributes.name_ar'),
            'phone_number' => __('attributes.phone_number'),
            'email' => __('attributes.email'),
            'business_name_en' => __('attributes.business_name_en'),
            'business_name_ar' => __('attributes.business_name_ar'),
            'business_address_en' => __('attributes.business_address_en'),
            'business_address_ar' => __('attributes.business_address_ar'),
            'business_type' => __('attributes.business_type'),
            'subscription_plan' => __('attributes.subscription_plan'),
            'subscription_period' => __('attributes.subscription_period'),
            'preferred_language' => __('attributes.preferred_language'),
        ];
    }
}
