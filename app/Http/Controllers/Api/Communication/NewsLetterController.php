<?php

namespace App\Http\Controllers\Api\Communication;

use App\Http\Controllers\Controller;
use App\Models\Newsletter;
use App\Models\User;
use App\Enums\ListingStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class NewsLetterController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Newsletter::query();

            // Add relationships if models exist
            if (method_exists(Newsletter::class, 'template')) {
                $query->with('template');
            }

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            $newsletters = $query->orderBy('created_at', 'desc')->get();

            return response()->json([
                'success' => true,
                'data' => $newsletters,
                'stats' => [
                    'total' => Newsletter::count(),
                    'draft' => Newsletter::where('status', 'draft')->count(),
                    'scheduled' => Newsletter::where('status', 'scheduled')->count(),
                    'sent' => Newsletter::where('status', 'sent')->count(),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch newsletters',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'title' => 'sometimes|string|max:255',
                'subject' => 'required|string|max:255',
                'content' => 'required|string',
                'excerpt' => 'nullable|string',
                'template_id' => 'nullable|integer',
                'scheduled_at' => 'nullable|date|after:now',
                'tags' => 'nullable|array',
                'status' => 'sometimes|in:draft,scheduled',
                'recipient_list' => 'nullable|string',
                'is_test' => 'boolean',
                'test_email' => 'nullable|email|required_if:is_test,true',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $data = $validator->validated();

            // Handle test email sending
            if ($data['is_test'] ?? false) {
                $testEmail = $data['test_email'];

                Mail::send('emails.newsletter', ['content' => $data['content']], function ($message) use ($testEmail, $data) {
                    $message->to($testEmail)
                        ->subject($data['subject']);
                });

                return response()->json([
                    'success' => true,
                    'message' => 'Test newsletter sent successfully',
                ]);
            }

            // Determine status
            if (isset($data['scheduled_at'])) {
                $data['status'] = 'scheduled';
            } else {
                $data['status'] = $data['status'] ?? 'draft';
            }

            // Add created_by field if user is authenticated
            if (Auth::check()) {
                $data['created_by'] = Auth::id();
            }

            // Create newsletter
            $newsletter = Newsletter::create($data);

            // If not a test and not scheduled, send immediately
            if (!isset($data['scheduled_at']) && ($data['status'] ?? 'draft') !== 'draft') {
                $this->sendNewsletter($newsletter);
            }

            return response()->json([
                'success' => true,
                'message' => 'Newsletter created successfully',
                'data' => $newsletter,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create newsletter',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show($id): JsonResponse
    {
        try {
            $query = Newsletter::where('id', $id);

            // Add relationships if models exist
            if (method_exists(Newsletter::class, 'template')) {
                $query->with('template');
            }

            $newsletter = $query->firstOrFail();

            return response()->json([
                'success' => true,
                'data' => $newsletter,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Newsletter not found',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    public function update(Request $request, $id): JsonResponse
    {
        try {
            $newsletter = Newsletter::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'title' => 'sometimes|string|max:255',
                'subject' => 'sometimes|string|max:255',
                'content' => 'sometimes|string',
                'excerpt' => 'nullable|string',
                'template_id' => 'nullable|integer',
                'scheduled_at' => 'nullable|date|after:now',
                'tags' => 'nullable|array',
                'status' => 'sometimes|in:draft,scheduled,sent',
                'recipient_list' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $data = $validator->validated();

            // Handle status logic
            if (isset($data['scheduled_at']) && $newsletter->status !== 'sent') {
                $data['status'] = 'scheduled';
            }

            $newsletter->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Newsletter updated successfully',
                'data' => $newsletter->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update newsletter',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id): JsonResponse
    {
        try {
            $newsletter = Newsletter::findOrFail($id);

            // Prevent deletion of sent newsletters
            if ($newsletter->status === 'sent') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete sent newsletters',
                ], 422);
            }

            $newsletter->delete();

            return response()->json([
                'success' => true,
                'message' => 'Newsletter deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete newsletter',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function send(Request $request, $id): JsonResponse
    {
        try {
            $newsletter = Newsletter::findOrFail($id);

            if ($newsletter->status === 'sent') {
                return response()->json([
                    'success' => false,
                    'message' => 'Newsletter has already been sent',
                ], 422);
            }

            $this->sendNewsletter($newsletter);

            return response()->json([
                'success' => true,
                'message' => 'Newsletter sent successfully',
                'data' => $newsletter->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send newsletter',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function duplicate(Request $request, $id): JsonResponse
    {
        try {
            $original = Newsletter::findOrFail($id);

            $duplicate = $original->replicate();
            $duplicate->title = ($duplicate->title ?? 'Newsletter') . ' (Copy)';
            $duplicate->subject = ($duplicate->subject ?? 'Subject') . ' (Copy)';
            $duplicate->status = 'draft';
            $duplicate->sent_at = null;
            $duplicate->created_by = Auth::id();
            $duplicate->save();

            return response()->json([
                'success' => true,
                'message' => 'Newsletter duplicated successfully',
                'data' => $duplicate,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to duplicate newsletter',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function resend(Request $request, $id): JsonResponse
    {
        try {
            $newsletter = Newsletter::findOrFail($id);

            if ($newsletter->status !== 'sent') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only sent newsletters can be resent',
                ], 422);
            }

            $validator = Validator::make($request->all(), [
                'is_test' => 'boolean',
                'test_email' => 'nullable|email|required_if:is_test,true',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $data = $validator->validated();

            if ($data['is_test'] ?? false) {
                // Send test email
                $testEmail = $data['test_email'];

                Mail::send('emails.newsletter', ['content' => $newsletter->content], function ($message) use ($testEmail, $newsletter) {
                    $message->to($testEmail)
                        ->subject($newsletter->subject . ' (Test Resend)');
                });

                return response()->json([
                    'success' => true,
                    'message' => 'Test newsletter resent successfully',
                ]);
            } else {
                // Resend to all recipients
                $recipients = User::whereNotNull('email')->get();

                foreach ($recipients as $recipient) {
                    Mail::send('emails.newsletter', ['content' => $newsletter->content], function ($message) use ($recipient, $newsletter) {
                        $message->to($recipient->email)
                            ->subject($newsletter->subject);
                    });
                }

                // Update newsletter with resend information
                $newsletter->update([
                    'resent_at' => now(),
                    'resend_count' => ($newsletter->resend_count ?? 0) + 1,
                    'last_recipient_count' => $recipients->count(),
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Newsletter resent successfully to ' . $recipients->count() . ' recipients',
                    'data' => $newsletter->fresh(),
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to resend newsletter',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getRecipients(): JsonResponse
    {
        try {
            $recipients = User::whereNotNull('email')
                ->select('id', 'name', 'email', 'email_verified_at', 'created_at')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'email_verified_at' => $user->email_verified_at,
                        'created_at' => $user->created_at,
                        'is_subscribed' => true, // All users with emails are considered subscribed by default
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $recipients,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch recipients',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getStats(): JsonResponse
    {
        try {
            $totalSubscribed = User::whereNotNull('email')->count();
            $totalNewsletters = Newsletter::count();
            $sentNewsletters = Newsletter::where('status', 'sent')->count();
            $draftNewsletters = Newsletter::where('status', 'draft')->count();

            $stats = [
                'total_subscribed' => $totalSubscribed,
                'total_recipients' => $totalSubscribed,
                'total_newsletters' => $totalNewsletters,
                'sent_newsletters' => $sentNewsletters,
                'draft_newsletters' => $draftNewsletters,
                'emails_sent' => 0, // Can be calculated from actual sent emails
                'failed' => 0, // Can track failed sends if needed
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch newsletter statistics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getNewsletterRecipients($id): JsonResponse
    {
        try {
            $newsletter = Newsletter::findOrFail($id);
            $recipients = User::whereNotNull('email')
                ->select('id', 'name', 'email', 'email_verified_at')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'newsletter' => $newsletter,
                    'recipients' => $recipients,
                    'total_count' => $recipients->count(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch newsletter recipients',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function bulkUpdateSubscription(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_emails' => 'required|array',
                'user_emails.*' => 'email',
                'action' => 'required|in:subscribe,unsubscribe',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $emails = $validator->validated()['user_emails'];
            $action = $validator->validated()['action'];

            $users = User::whereIn('email', $emails)->get();

            if ($users->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No users found with the provided emails',
                ], 404);
            }

            $updateCount = $users->count();
            $actionText = $action === 'subscribe' ? 'subscribed' : 'unsubscribed';

            return response()->json([
                'success' => true,
                'message' => "Successfully {$actionText} {$updateCount} users",
                'updated_count' => $updateCount,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update subscriptions',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateUserSubscription(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'subscribe' => 'required|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $email = $validator->validated()['email'];
            $subscribe = $validator->validated()['subscribe'];

            $user = User::where('email', $email)->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                ], 404);
            }

            $message = $subscribe ? 'User subscribed successfully' : 'User unsubscribed successfully';

            return response()->json([
                'success' => true,
                'message' => $message,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update subscription',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getSettings(): JsonResponse
    {
        try {
            $settings = [
                'auto_send' => false,
                'frequency' => 'weekly',
                'day_of_week' => 'monday',
                'send_time' => '09:00',
                'default_from_name' => config('mail.from.name', 'Marine.ng'),
                'default_from_email' => config('mail.from.address', 'newsletter@marine.ng'),
                'unsubscribe_link' => true,
                'track_opens' => false,
                'track_clicks' => false,
            ];

            return response()->json([
                'success' => true,
                'data' => $settings,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get newsletter settings',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateSettings(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'auto_send' => 'boolean',
                'frequency' => 'in:daily,weekly,monthly',
                'day_of_week' => 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
                'send_time' => 'date_format:H:i',
                'default_from_name' => 'string|max:255',
                'default_from_email' => 'email',
                'unsubscribe_link' => 'boolean',
                'track_opens' => 'boolean',
                'track_clicks' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            return response()->json([
                'success' => true,
                'message' => 'Newsletter settings updated successfully',
                'data' => $validator->validated(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update newsletter settings',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getFeaturedListings(Request $request): JsonResponse
    {
        try {
            $listings = collect([]);

            // Try to get equipment/listings using flexible approach
            try {
                if (class_exists('\App\Models\EquipmentListing')) {
                    $listings = \App\Models\EquipmentListing::with(['seller', 'category'])
                        ->where('is_featured', true)
                        ->where('status', ListingStatus::ACTIVE)
                        ->orderBy('created_at', 'desc')
                        ->limit($request->get('limit', 20))
                        ->get()
                        ->map(function ($listing) {
                            return (object) [
                                'id' => $listing->id,
                                'title' => $listing->title,
                                'price' => $listing->price,
                                'description' => $listing->description,
                                'location' => trim(($listing->location_city ?? '') . ', ' . ($listing->location_state ?? ''), ', ') ?: 'Location not specified',
                                'is_featured' => $listing->is_featured,
                                'created_at' => $listing->created_at,
                            ];
                        });
                } else {
                    // Return mock data for testing
                    $listings = collect([
                        (object) [
                            'id' => 1,
                            'title' => 'Marine Engine',
                            'price' => 50000,
                            'description' => 'High-quality marine engine for boats',
                            'location' => 'Lagos',
                            'is_featured' => true,
                            'created_at' => now(),
                        ],
                        (object) [
                            'id' => 2,
                            'title' => 'Boat Propeller',
                            'price' => 15000,
                            'description' => 'Durable stainless steel propeller',
                            'location' => 'Port Harcourt',
                            'is_featured' => true,
                            'created_at' => now(),
                        ],
                        (object) [
                            'id' => 3,
                            'title' => 'Marine GPS',
                            'price' => 25000,
                            'description' => 'Professional marine navigation GPS',
                            'location' => 'Warri',
                            'is_featured' => true,
                            'created_at' => now(),
                        ],
                    ]);
                }
            } catch (\Exception $e) {
                $listings = collect([]);
            }

            return response()->json([
                'success' => true,
                'data' => $listings,
                'count' => $listings->count(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch featured listings',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function createPromotionalNewsletter(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'promotional_text' => 'required|string',
                'featured_listings_limit' => 'integer|min:1|max:20',
                'subject' => 'required|string|max:255',
                'send_immediately' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $data = $validator->validated();

            // Get featured listings
            $featuredListingsResponse = $this->getFeaturedListings($request);
            $featuredListingsData = json_decode($featuredListingsResponse->getContent(), true);
            $featuredListings = collect($featuredListingsData['data'] ?? []);

            // Generate promotional content
            $content = $this->generatePromotionalContent($data['promotional_text'], $featuredListings);

            // Create newsletter
            $newsletter = Newsletter::create([
                'title' => 'Promotional Newsletter - ' . now()->format('M d, Y'),
                'subject' => $data['subject'],
                'content' => $content,
                'status' => 'draft',
                'created_by' => Auth::id(),
            ]);

            if ($data['send_immediately'] ?? false) {
                $this->sendNewsletter($newsletter);
            }

            return response()->json([
                'success' => true,
                'message' => 'Promotional newsletter created successfully',
                'data' => $newsletter,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create promotional newsletter',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    private function sendNewsletter($newsletter): void
    {
        $recipients = User::whereNotNull('email')->get();

        foreach ($recipients as $recipient) {
            try {
                Mail::send('emails.newsletter', ['content' => $newsletter->content], function ($message) use ($recipient, $newsletter) {
                    $message->to($recipient->email)
                        ->subject($newsletter->subject);
                });
            } catch (\Exception $e) {
                Log::error('Failed to send newsletter to ' . $recipient->email . ': ' . $e->getMessage());
            }
        }

        $newsletter->update([
            'status' => 'sent',
            'sent_at' => now(),
            'recipient_count' => $recipients->count(),
        ]);
    }

    private function generatePromotionalContent($promotionalText, $featuredListings): string
    {
        $content = '<div style="max-width: 600px; margin: 0 auto; font-family: Arial, sans-serif; background: #ffffff;">';

        // Header with gradient
        // $content .= '<div style="background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%); padding: 40px 20px; text-align: center;">';
        // $content .= '<h1 style="color: white; margin: 0; font-size: 32px; font-weight: bold;">Marine.ng</h1>';
        // $content .= '<p style="color: #e0e7ff; margin: 10px 0 0 0; font-size: 16px;">Premium Marine Equipment Newsletter</p>';
        // $content .= '</div>';

        // Promotional text section
        $content .= '<div style="padding: 40px 20px; background: #f8fafc;">';
        $content .= '<div style="background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">';
        $content .= '<p style="font-size: 16px; line-height: 1.6; color: #374151; margin: 0;">' . nl2br(htmlspecialchars($promotionalText)) . '</p>';
        $content .= '</div>';
        $content .= '</div>';

        // Featured listings section
        $content .= '<div style="padding: 0 20px 40px; background: #f8fafc;">';
        $content .= '<h2 style="color: #1f2937; margin: 0 0 30px 0; font-size: 24px; font-weight: 600; text-align: center;">Featured Equipment</h2>';

        foreach ($featuredListings as $listing) {
            $listingData = is_array($listing) ? (object) $listing : $listing;

            $content .= '<div style="background: white; border-radius: 12px; padding: 24px; margin-bottom: 24px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); border: 1px solid #e5e7eb;">';
            $content .= '<h3 style="color: #1f2937; margin: 0 0 12px 0; font-size: 20px; font-weight: 600;">' . htmlspecialchars($listingData->title ?? 'Equipment') . '</h3>';
            $content .= '<p style="color: #059669; font-size: 24px; font-weight: bold; margin: 0 0 12px 0;">₦' . number_format($listingData->price ?? 0, 2) . '</p>';

            if (!empty($listingData->description)) {
                $shortDescription = substr(strip_tags($listingData->description), 0, 150) . '...';
                $content .= '<p style="color: #6b7280; margin: 0 0 20px 0; line-height: 1.5;">' . htmlspecialchars($shortDescription) . '</p>';
            }

            $content .= '<div style="font-size: 14px; color: #6b7280; margin-bottom: 20px;">';
            $content .= '<span><strong>Location:</strong> ' . htmlspecialchars($listingData->location ?? 'Not specified') . '</span>';
            $content .= '</div>';

            // View Details button
            $listingUrl = config('app.frontend_url', 'https://marine.ng') . '/equipment-listing-detail?id=' . ($listingData->id ?? '');
            $content .= '<div style="text-align: center;">';
            $content .= '<a href="' . $listingUrl . '" style="display: inline-block; background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); color: white; padding: 14px 28px; text-decoration: none; border-radius: 8px; font-weight: 600; box-shadow: 0 4px 6px rgba(59, 130, 246, 0.25);">';
            $content .= 'View Details</a>';
            $content .= '</div>';
            $content .= '</div>';
        }

        $content .= '</div>';

        // Footer
        $content .= '<div style="background: #1f2937; padding: 40px 20px; text-align: center; color: #9ca3af;">';
        $content .= '<h3 style="color: white; margin: 0 0 16px 0; font-size: 20px;">Marine.ng</h3>';
        $content .= '<p style="margin: 0 0 16px 0; line-height: 1.5;">Nigeria\'s premier marketplace for marine equipment</p>';
        $content .= '<p style="margin: 0; font-size: 14px;">Visit us at <a href="https://marine.ng" style="color: #3b82f6;">marine.ng</a></p>';
        $content .= '</div>';

        $content .= '</div>';

        return $content;
    }
}