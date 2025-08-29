<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListingFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category' => 'sometimes|exists:equipment_categories,id',
            'condition' => 'sometimes|string|in:new,excellent,good,fair,poor',
            'min_price' => 'sometimes|numeric|min:0',
            'max_price' => 'sometimes|numeric|min:0|gte:min_price',
            'location_state' => 'sometimes|string|max:100',
            'location_city' => 'sometimes|string|max:100',
            'brand' => 'sometimes|string|max:100',
            'search' => 'sometimes|string|max:255',
            'sort' => 'sometimes|string|in:price_asc,price_desc,newest,oldest,featured',
            'per_page' => 'sometimes|integer|min:1|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'category.exists' => 'The selected category does not exist.',
            'condition.in' => 'The condition must be one of: new, excellent, good, fair, poor.',
            'max_price.gte' => 'The maximum price must be greater than or equal to the minimum price.',
            'sort.in' => 'Invalid sort option.',
            'per_page.max' => 'Maximum 100 items per page.',
        ];
    }

    public function getFilters(): array
    {
        return $this->only([
            'category',
            'condition',
            'min_price',
            'max_price',
            'location_state',
            'location_city',
            'brand',
            'search',
        ]);
    }

    public function getSortBy(): string
    {
        return $this->input('sort', 'newest');
    }

    public function getPerPage(): int
    {
        return $this->input('per_page', 15);
    }
}