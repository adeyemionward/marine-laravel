<?php

namespace App\DTOs;

use App\Enums\EquipmentCondition;
use App\Enums\ListingStatus;

readonly class ListingFilterDTO
{
    public function __construct(
        public ?int $categoryId = null,
        public ?string $condition = null,
        public ?string $state = null,
        public ?string $city = null,
        public ?float $priceMin = null,
        public ?float $priceMax = null,
        public ?string $brand = null,
        public ?int $yearMin = null,
        public ?int $yearMax = null,
        public ?bool $featuredOnly = false,
        public ?bool $verifiedOnly = false,
        public ?bool $withImages = false,
        public string $sortBy = 'created_at',
        public string $sortDirection = 'desc',
        public int $page = 1,
        public int $perPage = 20,
        public ?string $currency = null,
        public ?bool $negotiable = null,
        public ?bool $deliveryAvailable = null,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            categoryId: $data['category_id'] ?? null,
            condition: $data['condition'] ?? null,
            state: $data['state'] ?? null,
            city: $data['city'] ?? null,
            priceMin: isset($data['price_min']) ? (float) $data['price_min'] : null,
            priceMax: isset($data['price_max']) ? (float) $data['price_max'] : null,
            brand: $data['brand'] ?? null,
            yearMin: isset($data['year_min']) ? (int) $data['year_min'] : null,
            yearMax: isset($data['year_max']) ? (int) $data['year_max'] : null,
            featuredOnly: filter_var($data['featured_only'] ?? false, FILTER_VALIDATE_BOOLEAN),
            verifiedOnly: filter_var($data['verified_only'] ?? false, FILTER_VALIDATE_BOOLEAN),
            withImages: filter_var($data['with_images'] ?? false, FILTER_VALIDATE_BOOLEAN),
            sortBy: $data['sort_by'] ?? 'created_at',
            sortDirection: $data['sort_direction'] ?? 'desc',
            page: max(1, (int) ($data['page'] ?? 1)),
            perPage: min(50, max(1, (int) ($data['per_page'] ?? 20))),
            currency: $data['currency'] ?? null,
            negotiable: isset($data['negotiable']) ? filter_var($data['negotiable'], FILTER_VALIDATE_BOOLEAN) : null,
            deliveryAvailable: isset($data['delivery_available']) ? filter_var($data['delivery_available'], FILTER_VALIDATE_BOOLEAN) : null,
        );
    }

    public function toArray(): array
    {
        return [
            'category_id' => $this->categoryId,
            'condition' => $this->condition,
            'state' => $this->state,
            'city' => $this->city,
            'price_min' => $this->priceMin,
            'price_max' => $this->priceMax,
            'brand' => $this->brand,
            'year_min' => $this->yearMin,
            'year_max' => $this->yearMax,
            'featured_only' => $this->featuredOnly,
            'verified_only' => $this->verifiedOnly,
            'with_images' => $this->withImages,
            'sort_by' => $this->sortBy,
            'sort_direction' => $this->sortDirection,
            'page' => $this->page,
            'per_page' => $this->perPage,
            'currency' => $this->currency,
            'negotiable' => $this->negotiable,
            'delivery_available' => $this->deliveryAvailable,
        ];
    }

    public function hasFilters(): bool
    {
        return $this->categoryId !== null ||
               $this->condition !== null ||
               $this->state !== null ||
               $this->city !== null ||
               $this->priceMin !== null ||
               $this->priceMax !== null ||
               $this->brand !== null ||
               $this->yearMin !== null ||
               $this->yearMax !== null ||
               $this->featuredOnly ||
               $this->verifiedOnly ||
               $this->withImages ||
               $this->currency !== null ||
               $this->negotiable !== null ||
               $this->deliveryAvailable !== null;
    }
}