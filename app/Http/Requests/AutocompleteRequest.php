<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class AutocompleteRequest extends FormRequest
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
            'query' => 'sometimes|string|max:100',
            'limit' => 'sometimes|integer|min:1|max:20',
            'type' => 'sometimes|string|in:all,products,restaurants,categories',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'query.string' => __('validation.search_query_must_be_string'),
            'query.max' => __('validation.search_query_max_length'),
            'limit.integer' => __('validation.limit_must_be_integer'),
            'limit.min' => __('validation.limit_min_value'),
            'limit.max' => __('validation.limit_max_value'),
            'type.in' => __('validation.invalid_autocomplete_type'),
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'query' => __('validation.attributes.search_query'),
            'limit' => __('validation.attributes.limit'),
            'type' => __('validation.attributes.autocomplete_type'),
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => __('api.validation_failed'),
                'errors' => $validator->errors(),
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
            $this->merge([
                'query' => trim($this->query),
            ]);
        }

        // Set default values
        $this->merge([
            'limit' => $this->get('limit', 10),
            'type' => $this->get('type', 'all'),
        ]);
    }
}
