<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Inquiry;
use App\Models\EquipmentListing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class InquiryController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'listing_id' => 'required|exists:equipment_listings,id',
                'inquirer_name' => 'required|string|max:255',
                'inquirer_email' => 'required|email|max:255',
                'inquirer_phone' => 'nullable|string|max:20',
                'subject' => 'required|string|max:255',
                'message' => 'required|string|max:2000',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $user = Auth::user();
            $inquirerProfile = $user ? $user->profile : null;

            $inquiry = Inquiry::create([
                'listing_id' => $request->listing_id,
                'inquirer_id' => $inquirerProfile?->id,
                'inquirer_name' => $request->inquirer_name,
                'inquirer_email' => $request->inquirer_email,
                'inquirer_phone' => $request->inquirer_phone,
                'subject' => $request->subject,
                'message' => $request->message,
                'status' => 'pending',
            ]);

            // Load relationships for response
            $inquiry->load(['listing:id,title,seller_id', 'inquirer:id,full_name,email']);

            return response()->json([
                'success' => true,
                'message' => 'Inquiry submitted successfully',
                'data' => $inquiry,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit inquiry',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $query = Inquiry::with(['listing:id,title,seller_id', 'inquirer:id,full_name,email']);

            // Filter by listing if provided
            if ($request->has('listing_id')) {
                $query->where('listing_id', $request->listing_id);
            }

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Filter by email for non-authenticated users
            if ($request->has('email') && !Auth::check()) {
                $query->where('inquirer_email', $request->email);
            }

            // If user is authenticated, show their inquiries only (unless admin)
            if (Auth::check() && !Auth::user()->hasAnyRole(['admin', 'moderator'])) {
                $query->where('inquirer_id', Auth::user()->profile?->id);
            }

            $inquiries = $query
                ->orderBy('created_at', 'desc')
                ->paginate(20);

            return response()->json([
                'success' => true,
                'data' => $inquiries->items(),
                'meta' => [
                    'current_page' => $inquiries->currentPage(),
                    'per_page' => $inquiries->perPage(),
                    'total' => $inquiries->total(),
                    'last_page' => $inquiries->lastPage(),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch inquiries',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show(Request $request, $id): JsonResponse
    {
        try {
            $inquiry = Inquiry::with(['listing:id,title,seller_id', 'inquirer:id,full_name,email'])
                ->findOrFail($id);

            // Check permissions
            $user = Auth::user();
            if (!$user || (!$user->hasAnyRole(['admin', 'moderator']) &&
                $inquiry->inquirer_id !== $user->profile?->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access',
                ], 403);
            }

            return response()->json([
                'success' => true,
                'data' => $inquiry,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch inquiry',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, $id): JsonResponse
    {
        try {
            $inquiry = Inquiry::findOrFail($id);

            // Only admin/moderator can update inquiries
            if (!Auth::user() || !Auth::user()->hasAnyRole(['admin', 'moderator'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access',
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'status' => 'sometimes|in:pending,responded,closed',
                'admin_notes' => 'nullable|string|max:1000',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $updateData = $request->only(['status', 'admin_notes']);

            // If status is being changed to responded, set responded_at
            if (isset($updateData['status']) && $updateData['status'] === 'responded') {
                $updateData['responded_at'] = now();
            }

            $inquiry->update($updateData);

            return response()->json([
                'success' => true,
                'message' => 'Inquiry updated successfully',
                'data' => $inquiry->fresh(['listing:id,title,seller_id', 'inquirer:id,full_name,email']),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update inquiry',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getForListing(Request $request, $listingId): JsonResponse
    {
        try {
            $listing = EquipmentListing::findOrFail($listingId);

            // Check if user owns this listing or is admin
            $user = Auth::user();
            $userProfile = $user?->profile;

            if (!$user || (!$user->hasAnyRole(['admin', 'moderator']) &&
                $listing->seller_id !== $userProfile?->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access',
                ], 403);
            }

            $inquiries = Inquiry::with(['inquirer:id,full_name,email'])
                ->where('listing_id', $listingId)
                ->when($request->status, function ($query, $status) {
                    return $query->where('status', $status);
                })
                ->orderBy('created_at', 'desc')
                ->paginate(20);

            return response()->json([
                'success' => true,
                'data' => $inquiries->items(),
                'meta' => [
                    'current_page' => $inquiries->currentPage(),
                    'per_page' => $inquiries->perPage(),
                    'total' => $inquiries->total(),
                    'last_page' => $inquiries->lastPage(),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch listing inquiries',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
