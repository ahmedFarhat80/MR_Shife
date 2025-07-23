<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateProductRequest extends FormRequest
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
            'category_id' => 'required|integer|exists:categories,id',
            'name_en' => 'required|string|max:255',
            'name_ar' => 'nullable|string|max:255',
            'description_en' => 'nullable|string|max:1000',
            'description_ar' => 'nullable|string|max:1000',
            'price' => 'required|numeric|min:0|max:999999.99',
            'discount_price' => 'nullable|numeric|min:0|max:999999.99|lt:price',
            'sku' => 'nullable|string|max:100|unique:products,sku',
            'images' => 'nullable|array|max:5',
            'images.*' => 'string|max:255',
            'preparation_time' => 'nullable|integer|min:1|max:300',
            'calories' => 'nullable|integer|min:0|max:9999',
            'ingredients_en' => 'nullable|array',
            'ingredients_en.*' => 'string|max:255',
            'ingredients_ar' => 'nullable|array',
            'ingredients_ar.*' => 'string|max:255',
            'allergens_en' => 'nullable|array',
            'allergens_en.*' => 'string|max:255',
            'allergens_ar' => 'nullable|array',
            'allergens_ar.*' => 'string|max:255',
            'is_vegetarian' => 'nullable|boolean',
            'is_vegan' => 'nullable|boolean',
            'is_gluten_free' => 'nullable|boolean',
            'is_spicy' => 'nullable|boolean',
            'is_available' => 'nullable|boolean',
            'is_featured' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0',
            'stock_quantity' => 'nullable|integer|min:0',
            'track_stock' => 'nullable|boolean',
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
            'category_id.required' => 'Category is required.',
            'category_id.exists' => 'Selected category does not exist.',
            'name_en.required' => 'English name is required.',
            'price.required' => 'Price is required.',
            'price.min' => 'Price must be greater than or equal to 0.',
            'discount_price.lt' => 'Discount price must be less than regular price.',
            'sku.unique' => 'SKU already exists.',
            'images.max' => 'Maximum 5 images allowed.',
            'preparation_time.min' => 'Preparation time must be at least 1 minute.',
            'preparation_time.max' => 'Preparation time cannot exceed 300 minutes.',
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
            'discount_price' => 'discount price',
            'preparation_time' => 'preparation time',
            'ingredients_en' => 'English ingredients',
            'ingredients_ar' => 'Arabic ingredients',
            'allergens_en' => 'English allergens',
            'allergens_ar' => 'Arabic allergens',
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Validate that internal category belongs to the authenticated merchant
            $user = $this->user();
            if ($user && $this->internal_category_id) {
                $categoryExists = \App\Models\InternalCategory::where('id', $this->internal_category_id)
                    ->where('merchant_id', $user->id)
                    ->exists();

                if (!$categoryExists) {
                    $validator->errors()->add('internal_category_id', 'Internal category does not belong to your business.');
                }
            }
        });
    }
}
