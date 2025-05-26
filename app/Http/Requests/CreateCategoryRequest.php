<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateCategoryRequest extends FormRequest
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
            'description_en' => 'nullable|string|max:500',
            'description_ar' => 'nullable|string|max:500',
            'image' => 'nullable|string|max:255',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
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
            'name_en.required' => 'English name is required.',
            'name_en.max' => 'English name cannot exceed 255 characters.',
            'name_ar.max' => 'Arabic name cannot exceed 255 characters.',
            'description_en.max' => 'English description cannot exceed 500 characters.',
            'description_ar.max' => 'Arabic description cannot exceed 500 characters.',
            'sort_order.min' => 'Sort order must be greater than or equal to 0.',
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
            'name_en' => 'English name',
            'name_ar' => 'Arabic name',
            'description_en' => 'English description',
            'description_ar' => 'Arabic description',
            'sort_order' => 'sort order',
        ];
    }
}
