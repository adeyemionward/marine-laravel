<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EquipmentReview;
use App\Models\EquipmentListing;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class EquipmentReviewController extends Controller
{
    /**
     * Get all reviews for a specific equipment listing
     */
    public function index(Request $request, $listingId): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 10);
            $sortBy = $request->get('sort_by', 'recent'); // recent, helpful, rating_high, rating_low

            $query = EquipmentReview::with(['user:id,name,email'])
                ->where('equipment_listing_id', $listingId)
                ->approved();

            // Apply sorting
            switch ($sortBy) {
                case 'helpful':
                    $query->orderBy('helpful_count', 'desc');
                    break;
                case 'rating_high':
                    $query->orderBy('rating', 'desc');
                    break;
                case 'rating_low':
                    $query->orderBy('rating', 'asc');
                    break;
                case 'recent':
                default:
                    $query->orderBy('created_at', 'desc');
                    break;
            }

            $reviews = $query->paginate($perPage);

            // Calculate rating statistics
            $listing = EquipmentListing::find($listingId);
            $stats = [
                'total_reviews' => EquipmentReview::where('equipment_listing_id', $listingId)->approved()->count(),
                'average_rating' => EquipmentReview::where('equipment_listing_id', $listingId)
                    ->approved()
                    ->avg('rating'),
                'rating_distribution' => [
                    '5' => EquipmentReview::where('equipment_listing_id', $listingId)->approved()->where('rating', 5)->count(),
                    '4' => EquipmentReview::where('equipment_listing_id', $listingId)->approved()->where('rating', 4)->count(),
                    '3' => EquipmentReview::where('equipment_listing_id', $listingId)->approved()->where('rating', 3)->count(),
                    '2' => EquipmentReview::where('equipment_listing_id', $listingId)->approved()->where('rating', 2)->count(),
                    '1' => EquipmentReview::where('equipment_listing_id', $listingId)->approved()->where('rating', 1)->count(),
                ],
            ];

            return response()->json([
                'success' => true,
                'data' => $reviews->items(),
                'stats' => $stats,
                'meta' => [
                    'current_page' => $reviews->currentPage(),
                    'per_page' => $reviews->perPage(),
                    'total' => $reviews->total(),
                    'last_page' => $reviews->lastPage(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch reviews',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a new review
     */
    public function store(Request $request, $listingId): JsonResponse
    {
        try {
            $user = $request->user();

            // Check if listing exists
            $listing = EquipmentListing::findOrFail($listingId);

            // Check if user has already reviewed this listing
            $existingReview = EquipmentReview::where('equipment_listing_id', $listingId)
                ->where('user_id', $user->id)
                ->first();

            if ($existingReview) {
                return response()->json([
                    'success' => false,
                    'message' => 'You have already reviewed this listing',
                ], 422);
            }

            // Validate request
            $validator = Validator::make($request->all(), [
                'rating' => 'required|integer|min:1|max:5',
                'title' => 'nullable|string|max:255',
                'comment' => 'required|string|max:1000',
                'images' => 'nullable|array|max:5',
                'images.*' => 'nullable',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Handle review images if provided
            $reviewImages = [];
            if ($request->has('images') && is_array($request->images)) {
                foreach ($request->images as $image) {
                    if (is_array($image) && isset($image['url'])) {
                        $reviewImages[] = $image;
                    }
                }
            }

            // Create review
            $review = EquipmentReview::create([
                'equipment_listing_id' => $listingId,
                'user_id' => $user->id,
                'rating' => $request->rating,
                'title' => $request->title,
                'comment' => $request->comment,
                'images' => !empty($reviewImages) ? $reviewImages : null,
                'status' => 'approved', // Auto-approve for now, you can add moderation later
            ]);

            $review->load('user:id,name,email');

            return response()->json([
                'success' => true,
                'message' => 'Review submitted successfully',
                'data' => $review,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit review',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update a review
     */
    public function update(Request $request, $listingId, $reviewId): JsonResponse
    {
        try {
            $user = $request->user();
            $review = EquipmentReview::where('id', $reviewId)
                ->where('equipment_listing_id', $listingId)
                ->where('user_id', $user->id)
                ->firstOrFail();

            // Validate request
            $validator = Validator::make($request->all(), [
                'rating' => 'sometimes|required|integer|min:1|max:5',
                'title' => 'nullable|string|max:255',
                'comment' => 'sometimes|required|string|max:1000',
                'images' => 'nullable|array|max:5',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Update review
            $review->update($request->only(['rating', 'title', 'comment', 'images']));
            $review->load('user:id,name,email');

            return response()->json([
                'success' => true,
                'message' => 'Review updated successfully',
                'data' => $review,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update review',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a review
     */
    public function destroy(Request $request, $listingId, $reviewId): JsonResponse
    {
        try {
            $user = $request->user();
            $review = EquipmentReview::where('id', $reviewId)
                ->where('equipment_listing_id', $listingId)
                ->where('user_id', $user->id)
                ->firstOrFail();

            $review->delete();

            return response()->json([
                'success' => true,
                'message' => 'Review deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete review',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mark review as helpful
     */
    public function markHelpful(Request $request, $listingId, $reviewId): JsonResponse
    {
        try {
            $review = EquipmentReview::where('id', $reviewId)
                ->where('equipment_listing_id', $listingId)
                ->firstOrFail();

            $review->increment('helpful_count');

            return response()->json([
                'success' => true,
                'message' => 'Thank you for your feedback',
                'data' => [
                    'helpful_count' => $review->helpful_count,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark review as helpful',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mark review as not helpful
     */
    public function markNotHelpful(Request $request, $listingId, $reviewId): JsonResponse
    {
        try {
            $review = EquipmentReview::where('id', $reviewId)
                ->where('equipment_listing_id', $listingId)
                ->firstOrFail();

            $review->increment('not_helpful_count');

            return response()->json([
                'success' => true,
                'message' => 'Thank you for your feedback',
                'data' => [
                    'not_helpful_count' => $review->not_helpful_count,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark review as not helpful',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Seller reply to a review
     */
    public function sellerReply(Request $request, $listingId, $reviewId): JsonResponse
    {
        try {
            $user = $request->user();
            $listing = EquipmentListing::findOrFail($listingId);

            // Check if user is the seller
            if ($listing->seller_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only the seller can reply to reviews',
                ], 403);
            }

            $review = EquipmentReview::where('id', $reviewId)
                ->where('equipment_listing_id', $listingId)
                ->firstOrFail();

            // Validate request
            $validator = Validator::make($request->all(), [
                'reply' => 'required|string|max:1000',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $review->update([
                'seller_reply' => $request->reply,
                'seller_replied_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Reply posted successfully',
                'data' => $review,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to post reply',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
