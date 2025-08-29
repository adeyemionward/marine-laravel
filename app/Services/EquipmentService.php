<?php

namespace App\Services;

use App\Contracts\EquipmentRepositoryInterface;
use App\DTOs\ListingFilterDTO;
use App\DTOs\CreateListingDTO;
use App\Models\EquipmentListing;
use App\Models\UserProfile;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class EquipmentService
{
    public function __construct(
        private EquipmentRepositoryInterface $repository
    ) {}

    public function getListings(ListingFilterDTO $filters): LengthAwarePaginator
    {
        return $this->repository->getActiveListings($filters);
    }

    public function getFeaturedListings(int $limit = 10): Collection
    {
        return $this->repository->getFeaturedListings($limit);
    }

    public function getPopularListings(int $limit = 10): Collection
    {
        return $this->repository->getPopularListings($limit);
    }

    public function searchListings(string $query, ListingFilterDTO $filters): LengthAwarePaginator
    {
        return $this->repository->searchListings($query, $filters);
    }

    public function getListingDetail(int $id, bool $incrementView = true): ?EquipmentListing
    {
        $listing = $this->repository->findById($id);
        
        if ($listing && $incrementView) {
            $this->repository->incrementViewCount($id);
        }

        return $listing;
    }

    public function getUserListings(int $userId, array $filters = []): LengthAwarePaginator
    {
        return $this->repository->getListingsByUser($userId, $filters);
    }

    public function createListing(CreateListingDTO $dto, UserProfile $user): EquipmentListing
    {
        return DB::transaction(function () use ($dto, $user) {
            $listing = $this->repository->create($dto);

            // Handle image uploads if any
            if ($dto->images) {
                $imagePaths = $this->processImageUploads($dto->images);
                $listing->update(['images' => $imagePaths]);
            }

            // Auto-publish if user has permissions
            if ($user->canManageListings() || $user->hasActiveSubscription()) {
                $listing->publish();
            }

            return $listing;
        });
    }

    public function updateListing(int $id, array $data, UserProfile $user): bool
    {
        $listing = $this->repository->findById($id);
        
        if (!$listing) {
            return false;
        }

        // Check ownership or admin permissions
        if ($listing->seller_id !== $user->id && !$user->canManageListings()) {
            throw new \Exception('Unauthorized to update this listing');
        }

        return DB::transaction(function () use ($id, $data, $listing) {
            // Handle image updates
            if (isset($data['images'])) {
                $imagePaths = $this->processImageUploads($data['images']);
                $data['images'] = $imagePaths;
                
                // Delete old images if needed
                $this->deleteOldImages($listing->images ?? [], $imagePaths);
            }

            return $this->repository->update($id, $data);
        });
    }

    public function deleteListing(int $id, UserProfile $user): bool
    {
        $listing = $this->repository->findById($id);
        
        if (!$listing) {
            return false;
        }

        // Check ownership or admin permissions
        if ($listing->seller_id !== $user->id && !$user->canManageListings()) {
            throw new \Exception('Unauthorized to delete this listing');
        }

        return DB::transaction(function () use ($listing) {
            // Delete associated images
            if ($listing->images) {
                $this->deleteImages($listing->images);
            }

            return $listing->delete();
        });
    }

    public function toggleFavorite(int $listingId, UserProfile $user): array
    {
        $listing = $this->repository->findById($listingId);
        
        if (!$listing) {
            throw new \Exception('Listing not found');
        }

        $favorite = $user->favorites()->where('listing_id', $listingId)->first();
        
        if ($favorite) {
            $favorite->delete();
            $isFavorited = false;
        } else {
            $user->favorites()->create(['listing_id' => $listingId]);
            $isFavorited = true;
        }

        return [
            'is_favorited' => $isFavorited,
            'favorites_count' => $listing->favorites()->count()
        ];
    }

    public function approveListing(int $id, UserProfile $admin): bool
    {
        if (!$admin->canManageListings()) {
            throw new \Exception('Unauthorized to approve listings');
        }

        return $this->repository->update($id, [
            'status' => 'active',
            'is_verified' => true,
            'published_at' => now()
        ]);
    }

    public function rejectListing(int $id, string $reason, UserProfile $admin): bool
    {
        if (!$admin->canManageListings()) {
            throw new \Exception('Unauthorized to reject listings');
        }

        return $this->repository->update($id, [
            'status' => 'rejected',
            // Could store rejection reason in a separate table or JSON field
        ]);
    }

    public function markAsSold(int $id, UserProfile $user): bool
    {
        $listing = $this->repository->findById($id);
        
        if (!$listing) {
            return false;
        }

        if ($listing->seller_id !== $user->id) {
            throw new \Exception('Unauthorized to update this listing');
        }

        return $this->repository->update($id, ['status' => 'sold']);
    }

    public function getListingAnalytics(int $id, UserProfile $user): array
    {
        $listing = $this->repository->findById($id);
        
        if (!$listing) {
            throw new \Exception('Listing not found');
        }

        if ($listing->seller_id !== $user->id && !$user->canManageListings()) {
            throw new \Exception('Unauthorized to view analytics');
        }

        return [
            'views' => $listing->view_count,
            'inquiries' => $listing->inquiry_count,
            'favorites' => $listing->favorites()->count(),
            'conversations' => $listing->conversations()->count(),
            'days_active' => $listing->published_at ? 
                $listing->published_at->diffInDays(now()) : 0,
        ];
    }

    private function processImageUploads(array $images): array
    {
        $imagePaths = [];
        
        foreach ($images as $image) {
            if (is_string($image)) {
                // Already uploaded image path
                $imagePaths[] = $image;
            } elseif ($image instanceof \Illuminate\Http\UploadedFile) {
                // New upload
                $path = $image->store('equipment-images', 'public');
                $imagePaths[] = $path;
            }
        }

        return $imagePaths;
    }

    private function deleteImages(array $imagePaths): void
    {
        foreach ($imagePaths as $path) {
            if ($path && Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        }
    }

    private function deleteOldImages(array $oldImages, array $newImages): void
    {
        $imagesToDelete = array_diff($oldImages, $newImages);
        $this->deleteImages($imagesToDelete);
    }
}