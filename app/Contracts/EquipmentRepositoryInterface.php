<?php

namespace App\Contracts;

use App\DTOs\ListingFilterDTO;
use App\DTOs\CreateListingDTO;
use App\Models\EquipmentListing;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface EquipmentRepositoryInterface
{
    public function findById(int $id): ?EquipmentListing;
    
    public function findBySlug(string $slug): ?EquipmentListing;
    
    public function getActiveListings(ListingFilterDTO $filters): LengthAwarePaginator;
    
    public function getFeaturedListings(int $limit = 10): Collection;
    
    public function getListingsByUser(int $userId, array $filters = []): LengthAwarePaginator;
    
    public function create(CreateListingDTO $dto): EquipmentListing;
    
    public function update(int $id, array $data): bool;
    
    public function delete(int $id): bool;
    
    public function incrementViewCount(int $id): void;
    
    public function getPopularListings(int $limit = 10): Collection;
    
    public function searchListings(string $query, ListingFilterDTO $filters): LengthAwarePaginator;
}