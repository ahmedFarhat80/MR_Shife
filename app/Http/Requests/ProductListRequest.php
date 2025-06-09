<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductListRequest extends FormRequest
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
            // Pagination
            'page' => 'sometimes|integer|min:1',
            'per_page' => 'sometimes|integer|min:1|max:50',

            // Filtering
            'category_id' => 'sometimes|integer|exists:internal_categories,id',
            'food_nationality_id' => 'sometimes|integer|exists:food_nationalities,id',
            'merchant_id' => 'sometimes|integer|exists:merchants,id',
            'min_price' => 'sometimes|numeric|min:0',
            'max_price' => 'sometimes|numeric|min:0|gte:min_price',
            'is_vegetarian' => 'sometimes|boolean',
            'is_spicy' => 'sometimes|boolean',
            'is_featured' => 'sometimes|boolean',
            'is_popular' => 'sometimes|boolean',

            // Sorting
            'sort_by' => 'sometimes|string|in:price_asc,price_desc,rating_desc,popularity_desc,newest',

            // Search
            'search' => 'sometimes|string|max:255',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Clean and prepare search query
        if ($this->has('search')) {
            $searchValue = $this->get('search');
            if (is_string($searchValue)) {
                $this->merge([
                    'search' => trim($searchValue),
                ]);
            }
        }

        // Set default values
        $this->merge([
            'page' => $this->get('page', 1),
            'per_page' => min($this->get('per_page', 15), 50),
            'sort_by' => $this->get('sort_by', 'newest'),
        ]);
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'category_id.exists' => __('validation.category_not_found'),
            'food_nationality_id.exists' => __('validation.food_nationality_not_found'),
            'merchant_id.exists' => __('validation.merchant_not_found'),
            'max_price.gte' => __('validation.max_price_greater_than_min'),
            'per_page.max' => __('validation.per_page_max_50'),
            'sort_by.in' => __('validation.invalid_sort_option'),
        ];
    }
}
