<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Basic Information
            'name_en' => 'required|string|max:255',
            'name_ar' => 'nullable|string|max:255',
            'description_en' => 'nullable|string|max:1000',
            'description_ar' => 'nullable|string|max:1000',
            
            // Categories
            'internal_category_id' => 'nullable|exists:internal_categories,id',
            'food_nationality_id' => 'nullable|exists:food_nationalities,id',
            
            // Background
            'background_type' => ['required', Rule::in(['color', 'image'])],
            'background_value' => 'nullable|string|max:255',
            'background_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            
            // Pricing
            'base_price' => 'required|numeric|min:0|max:999999.99',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            
            // Availability & Timing
            'is_available' => 'boolean',
            'preparation_time' => 'required|integer|min:1|max:300',
            
            // Additional Fields
            'sku' => 'nullable|string|max:100|unique:products,sku',
            'calories' => 'nullable|integer|min:0|max:9999',
            'ingredients' => 'nullable|array',
            'allergens' => 'nullable|array',
            
            // Boolean flags
            'is_vegetarian' => 'boolean',
            'is_vegan' => 'boolean',
            'is_gluten_free' => 'boolean',
            'is_spicy' => 'boolean',
            'is_featured' => 'boolean',
            'track_stock' => 'boolean',
            
            // Stock & Order
            'sort_order' => 'nullable|integer|min:0',
            'stock_quantity' => 'nullable|integer|min:0',
            
            // Product Images
            'images' => 'nullable|array|max:10',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            
            // Option Groups
            'option_groups' => 'nullable|array',
            'option_groups.*.name_en' => 'required_with:option_groups|string|max:255',
            'option_groups.*.name_ar' => 'nullable|string|max:255',
            'option_groups.*.type' => ['required_with:option_groups', Rule::in(['size', 'addon', 'ingredient', 'customization'])],
            'option_groups.*.is_required' => 'boolean',
            'option_groups.*.min_selections' => 'nullable|integer|min:0',
            'option_groups.*.max_selections' => 'nullable|integer|min:0',
            'option_groups.*.sort_order' => 'nullable|integer|min:0',
            
            // Options
            'option_groups.*.options' => 'nullable|array',
            'option_groups.*.options.*.name_en' => 'required_with:option_groups.*.options|string|max:255',
            'option_groups.*.options.*.name_ar' => 'nullable|string|max:255',
            'option_groups.*.options.*.price_modifier' => 'nullable|numeric|min:-999.99|max:999.99',
            'option_groups.*.options.*.sort_order' => 'nullable|integer|min:0',
            'option_groups.*.options.*.image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name_en' => __('validation.attributes.name_en'),
            'name_ar' => __('validation.attributes.name_ar'),
            'description_en' => __('validation.attributes.description_en'),
            'description_ar' => __('validation.attributes.description_ar'),
            'internal_category_id' => __('validation.attributes.internal_category'),
            'food_nationality_id' => __('validation.attributes.food_nationality'),
            'background_type' => __('validation.attributes.background_type'),
            'background_value' => __('validation.attributes.background_value'),
            'base_price' => __('validation.attributes.base_price'),
            'discount_percentage' => __('validation.attributes.discount_percentage'),
            'preparation_time' => __('validation.attributes.preparation_time'),
            'sku' => __('validation.attributes.sku'),
            'calories' => __('validation.attributes.calories'),
            'ingredients' => __('validation.attributes.ingredients'),
            'allergens' => __('validation.attributes.allergens'),
            'sort_order' => __('validation.attributes.sort_order'),
            'stock_quantity' => __('validation.attributes.stock_quantity'),
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name_en.required' => __('validation.required', ['attribute' => __('validation.attributes.name_en')]),
            'base_price.required' => __('validation.required', ['attribute' => __('validation.attributes.base_price')]),
            'base_price.numeric' => __('validation.numeric', ['attribute' => __('validation.attributes.base_price')]),
            'preparation_time.required' => __('validation.required', ['attribute' => __('validation.attributes.preparation_time')]),
            'background_type.in' => __('validation.in', ['attribute' => __('validation.attributes.background_type')]),
            'images.*.image' => __('validation.image', ['attribute' => __('validation.attributes.image')]),
            'images.*.max' => __('validation.max.file', ['attribute' => __('validation.attributes.image'), 'max' => '5MB']),
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set default values
        $this->merge([
            'is_available' => $this->boolean('is_available', true),
            'is_vegetarian' => $this->boolean('is_vegetarian', false),
            'is_vegan' => $this->boolean('is_vegan', false),
            'is_gluten_free' => $this->boolean('is_gluten_free', false),
            'is_spicy' => $this->boolean('is_spicy', false),
            'is_featured' => $this->boolean('is_featured', false),
            'track_stock' => $this->boolean('track_stock', false),
        ]);

        // Set Arabic name fallback
        if (!$this->filled('name_ar') && $this->filled('name_en')) {
            $this->merge(['name_ar' => $this->input('name_en')]);
        }

        // Set Arabic description fallback
        if (!$this->filled('description_ar') && $this->filled('description_en')) {
            $this->merge(['description_ar' => $this->input('description_en')]);
        }
    }
}
