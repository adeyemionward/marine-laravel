<?php

namespace App\Repositories;

use App\Contracts\EquipmentRepositoryInterface;
use App\DTOs\ListingFilterDTO;
use App\DTOs\CreateListingDTO;
use App\Models\EquipmentListing;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Builder;

class EquipmentRepository implements EquipmentRepositoryInterface
{
    public function __construct(
        private EquipmentListing $model
    ) {}

    public function findById(int $id): ?EquipmentListing
    {
        return $this->model->with(['seller', 'category', 'favorites'])
            ->find($id);
    }

    public function findBySlug(string $slug): ?EquipmentListing
    {
        // Extract ID from slug (format: title-id)
        $id = (int) substr($slug, strrpos($slug, '-') + 1);
        return $this->findById($id);
    }

    public function getActiveListings(ListingFilterDTO $filters): LengthAwarePaginator
    {
        $query = $this->model->with(['seller', 'category'])
            ->active()
            ->published()
            ->notExpired();

        $this->applyFilters($query, $filters);
        $this->applySorting($query, $filters);

        return $query->paginate(
            perPage: $filters->perPage,
            page: $filters->page
        );
    }

    public function getFeaturedListings(int $limit = 10): Collection
    {
        return $this->model->with(['seller', 'category'])
            ->active()
            ->featured()
            ->published()
            ->notExpired()
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public function getListingsByUser(int $userId, array $filters = []): LengthAwarePaginator
    {
        $query = $this->model->with(['category'])
            ->where('seller_id', $userId);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderBy('created_at', 'desc')
            ->paginate($filters['per_page'] ?? 20);
    }

    public function create(CreateListingDTO $dto): EquipmentListing
    {
        return $this->model->create($dto->toArray());
    }

    public function update(int $id, array $data): bool
    {
        $listing = $this->model->find($id);
        if (!$listing) {
            return false;
        }

        return $listing->update($data);
    }

    public function delete(int $id): bool
    {
        $listing = $this->model->find($id);
        if (!$listing) {
            return false;
        }

        return $listing->delete();
    }

    public function incrementViewCount(int $id): void
    {
        $this->model->where('id', $id)->increment('view_count');
    }

    public function getPopularListings(int $limit = 10): Collection
    {
        return $this->model->with(['seller', 'category'])
            ->active()
            ->published()
            ->notExpired()
            ->orderBy('view_count', 'desc')
            ->orderBy('inquiry_count', 'desc')
            ->limit($limit)
            ->get();
    }

    public function searchListings(string $query, ListingFilterDTO $filters): LengthAwarePaginator
    {
        $searchQuery = $this->model->with(['seller', 'category'])
            ->active()
            ->published()
            ->notExpired()
            ->where(function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                  ->orWhere('description', 'like', "%{$query}%")
                  ->orWhere('brand', 'like', "%{$query}%")
                  ->orWhere('model', 'like', "%{$query}%")
                  ->orWhereJsonContains('tags', $query);
            });

        $this->applyFilters($searchQuery, $filters);
        $this->applySorting($searchQuery, $filters);

        return $searchQuery->paginate(
            perPage: $filters->perPage,
            page: $filters->page
        );
    }

    private function applyFilters(Builder $query, ListingFilterDTO $filters): void
    {
        if ($filters->categoryId) {
            $query->where('category_id', $filters->categoryId);
        }

        if ($filters->condition) {
            $query->where('condition', $filters->condition);
        }

        if ($filters->state) {
            $query->where('location_state', $filters->state);
        }

        if ($filters->city) {
            $query->where('location_city', $filters->city);
        }

        if ($filters->priceMin || $filters->priceMax) {
            $query->priceRange($filters->priceMin, $filters->priceMax);
        }

        if ($filters->brand) {
            $query->where('brand', 'like', "%{$filters->brand}%");
        }

        if ($filters->yearMin) {
            $query->where('year', '>=', $filters->yearMin);
        }

        if ($filters->yearMax) {
            $query->where('year', '<=', $filters->yearMax);
        }

        if ($filters->featuredOnly) {
            $query->featured();
        }

        if ($filters->verifiedOnly) {
            $query->verified();
        }

        if ($filters->withImages) {
            $query->whereNotNull('images')->where('images', '!=', '[]');
        }

        if ($filters->currency) {
            $query->where('currency', $filters->currency);
        }

        if ($filters->negotiable !== null) {
            $query->where('is_price_negotiable', $filters->negotiable);
        }

        if ($filters->deliveryAvailable !== null) {
            $query->where('delivery_available', $filters->deliveryAvailable);
        }
    }

    private function applySorting(Builder $query, ListingFilterDTO $filters): void
    {
        $allowedSorts = [
            'created_at', 'published_at', 'price', 'view_count', 
            'inquiry_count', 'title', 'year'
        ];

        $sortBy = in_array($filters->sortBy, $allowedSorts) 
            ? $filters->sortBy 
            : 'created_at';

        $direction = in_array($filters->sortDirection, ['asc', 'desc']) 
            ? $filters->sortDirection 
            : 'desc';

        // Special handling for featured listings
        if ($filters->featuredOnly) {
            $query->orderBy('is_featured', 'desc');
        }

        $query->orderBy($sortBy, $direction);
    }
}