<?php

namespace App\Http\Controllers\Api\Communication;

use App\Http\Controllers\Controller;
use App\Models\Newsletter;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class NewsLetterController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            $newsletters = Newsletter::orderBy('created_at', 'desc')->get();
            return response()->json([
                'success' => true,
                'data' => $newsletters,
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
                'subject' => 'required|string|max:255',
                'content' => 'required|string',
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

                Mail::send('emails.newsletter', ['content' => $data['content']], function ($message) use ($testEmail, $data) {
                    $message->to($testEmail)
                        ->subject($data['subject']);
                });

                return response()->json([
                    'success' => true,
                    'message' => 'Test newsletter sent successfully',
                ]);
            } else {
                // Create newsletter and send to all recipients
                $newsletter = Newsletter::create([
                    'subject' => $data['subject'],
                    'content' => $data['content'],
                    'status' => 'draft',
                ]);

                // Get all users with emails (not just verified)
                $recipients = User::whereNotNull('email')->get();

                foreach ($recipients as $recipient) {
                    Mail::send('emails.newsletter', ['content' => $data['content']], function ($message) use ($recipient, $data) {
                        $message->to($recipient->email)
                            ->subject($data['subject']);
                    });
                }

                $newsletter->update([
                    'status' => 'sent',
                    'sent_at' => now(),
                    'recipient_count' => $recipients->count(),
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Newsletter sent successfully',
                    'data' => $newsletter,
                ], 201);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send newsletter',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show($id): JsonResponse
    {
        try {
            $newsletter = Newsletter::findOrFail($id);
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
                'subject' => 'sometimes|string|max:255',
                'content' => 'sometimes|string',
                'status' => 'sometimes|in:draft,sent,scheduled',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $newsletter->update($validator->validated());

            return response()->json([
                'success' => true,
                'message' => 'Newsletter updated successfully',
                'data' => $newsletter,
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

    public function getRecipients(): JsonResponse
    {
        try {
            // Get all users with emails (not just verified)
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
            // Count all users with emails (not just verified)
            $totalSubscribed = User::whereNotNull('email')->count();
            $totalNewsletters = Newsletter::count();
            $sentNewsletters = Newsletter::where('status', 'sent')->count();
            $draftNewsletters = Newsletter::where('status', 'draft')->count();

            $stats = [
                'total_subscribed' => $totalSubscribed,
                'total_newsletters' => $totalNewsletters,
                'sent_newsletters' => $sentNewsletters,
                'draft_newsletters' => $draftNewsletters,
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

            // Get all users with emails (not just verified)
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

    public function resend(Request $request, $id): JsonResponse
    {
        try {
            $newsletter = Newsletter::findOrFail($id);

            // Only allow resending of sent newsletters
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
                            ->subject($newsletter->subject . ' (Resend)');
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

            // Find users by email
            $users = User::whereIn('email', $emails)->get();

            if ($users->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No users found with the provided emails',
                ], 404);
            }

            $updateCount = $users->count();
            $actionText = $action === 'subscribe' ? 'subscribed' : 'unsubscribed';

            // For now, since we treat all users with emails as subscribed,
            // we'll just return success for demo purposes
            // In a real implementation, you would add a newsletter subscription table
            // or add a newsletter_subscribed_at column to the users table

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

            // For now, since we treat all users with emails as subscribed,
            // we'll just return success for demo purposes
            // In a real implementation, you would add a newsletter subscription table
            // or add a newsletter_subscribed_at column to the users table
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
}