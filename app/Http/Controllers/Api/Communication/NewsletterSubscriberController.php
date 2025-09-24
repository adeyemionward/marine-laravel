<?php

namespace App\Http\Controllers\Api\Communication;

use App\Http\Controllers\Controller;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class NewsletterSubscriberController extends Controller
{
    public function index(Request $request)
    {
        $query = NewsletterSubscriber::query();

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('email', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%");
            });
        }

        $subscribers = $query->orderBy('created_at', 'desc')
                            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $subscribers,
            'stats' => [
                'total' => NewsletterSubscriber::count(),
                'active' => NewsletterSubscriber::active()->count(),
                'unsubscribed' => NewsletterSubscriber::where('status', 'unsubscribed')->count(),
            ]
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email|unique:newsletter_subscribers,email',
            'name' => 'nullable|string|max:255',
            'source' => 'nullable|string|max:255',
        ]);

        $subscriber = NewsletterSubscriber::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Subscriber added successfully',
            'data' => $subscriber
        ], 201);
    }

    public function getStats()
    {
        $stats = [
            'total_subscribers' => NewsletterSubscriber::count(),
            'active_subscribers' => NewsletterSubscriber::active()->count(),
            'unsubscribed' => NewsletterSubscriber::where('status', 'unsubscribed')->count(),
            'bounced' => NewsletterSubscriber::where('status', 'bounced')->count(),
            'recent_subscribers' => NewsletterSubscriber::where('created_at', '>=', now()->subDays(30))->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
}
