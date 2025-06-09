<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class SearchRequest extends FormRequest
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
            // Search Parameters
            'query' => 'required|string|min:2|max:100',
            'search_type' => 'sometimes|string|in:all,products,restaurants',
            'per_page' => 'sometimes|integer|min:1|max:50',
            'page' => 'sometimes|integer|min:1',

            // Product Filters
            'category_id' => 'sometimes|integer|exists:internal_categories,id',
            'food_nationality_id' => 'sometimes|integer|exists:food_nationalities,id',
            'merchant_id' => 'sometimes|integer|exists:merchants,id',
            'price_min' => 'sometimes|numeric|min:0',
            'price_max' => 'sometimes|numeric|min:0|gte:price_min',
            'is_vegetarian' => 'sometimes|boolean',
            'is_spicy' => 'sometimes|boolean',
            'has_discount' => 'sometimes|boolean',

            // Restaurant Filters
            'business_type' => 'sometimes|string|in:restaurant,cafe,bakery,grocery,pharmacy',
            'is_featured' => 'sometimes|boolean',
            'location_city' => 'sometimes|string|max:100',
            'location_area' => 'sometimes|string|max:100',

            // Location Filters
            'user_lat' => 'sometimes|numeric|between:-90,90',
            'user_lng' => 'sometimes|numeric|between:-180,180',
            'radius' => 'sometimes|numeric|min:1|max:50',

            // Sorting
            'sort_by' => 'sometimes|string|in:relevance,price,rating,distance,name',
            'sort_order' => 'sometimes|string|in:asc,desc',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'query.required' => __('validation.search_query_required'),
            'query.min' => __('validation.search_query_min_length'),
            'query.max' => __('validation.search_query_max_length'),
            'search_type.in' => __('validation.invalid_search_type'),
            'per_page.integer' => __('validation.per_page_must_be_integer'),
            'per_page.min' => __('validation.per_page_min_value'),
            'per_page.max' => __('validation.per_page_max_value'),
            'category_id.exists' => __('validation.category_not_found'),
            'food_nationality_id.exists' => __('validation.food_nationality_not_found'),
            'merchant_id.exists' => __('validation.merchant_not_found'),
            'price_min.numeric' => __('validation.price_min_must_be_numeric'),
            'price_max.numeric' => __('validation.price_max_must_be_numeric'),
            'price_max.gte' => __('validation.price_max_must_be_greater_than_min'),
            'business_type.in' => __('validation.invalid_business_type'),
            'user_lat.between' => __('validation.invalid_latitude'),
            'user_lng.between' => __('validation.invalid_longitude'),
            'radius.min' => __('validation.radius_min_value'),
            'radius.max' => __('validation.radius_max_value'),
            'sort_by.in' => __('validation.invalid_sort_field'),
            'sort_order.in' => __('validation.invalid_sort_order'),
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'query' => __('validation.attributes.search_query'),
            'search_type' => __('validation.attributes.search_type'),
            'per_page' => __('validation.attributes.per_page'),
            'category_id' => __('validation.attributes.category'),
            'food_nationality_id' => __('validation.attributes.food_nationality'),
            'merchant_id' => __('validation.attributes.merchant'),
            'price_min' => __('validation.attributes.minimum_price'),
            'price_max' => __('validation.attributes.maximum_price'),
            'business_type' => __('validation.attributes.business_type'),
            'location_city' => __('validation.attributes.city'),
            'location_area' => __('validation.attributes.area'),
            'user_lat' => __('validation.attributes.latitude'),
            'user_lng' => __('validation.attributes.longitude'),
            'radius' => __('validation.attributes.search_radius'),
            'sort_by' => __('validation.attributes.sort_field'),
            'sort_order' => __('validation.attributes.sort_order'),
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator)
    {
        $language = $this->header('X-Language', 'en');

        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => __('api.validation_failed'),
                'errors' => $validator->errors(),
                'suggestions' => app(\App\Services\SearchService::class)->getSearchSuggestions($language),
            ], 422)
        );
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Clean and prepare search query
        if ($this->has('query')) {
            $queryValue = $this->get('query');
            if (is_string($queryValue)) {
                $this->merge([
                    'query' => trim($queryValue),
                ]);
            }
        }

        // Set default values
        $this->merge([
            'search_type' => $this->get('search_type', 'all'),
            'per_page' => $this->get('per_page', 20),
            'page' => $this->get('page', 1),
            'sort_by' => $this->get('sort_by', 'relevance'),
            'sort_order' => $this->get('sort_order', 'desc'),
        ]);
    }
}
