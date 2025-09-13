    public function dashboardAnalytics(Request $request): JsonResponse
    {
        try {
            $now = Carbon::now();
            $lastMonth = $now->copy()->subMonth();
            
            // Get time range from request (default 30 days)
            $timeRange = $request->get('time_range', '30d');
            $days = (int) str_replace('d', '', $timeRange);
            $startDate = $now->copy()->subDays($days);

            // Core analytics from original method
            $analytics = [
                'overview' => [
                    'total_listings' => EquipmentListing::count(),
                    'active_listings' => EquipmentListing::where('status', 'active')->count(),
                    'total_users' => User::count(),
                    'verified_users' => UserProfile::where('is_verified', true)->count(),
                ],
                'recent' => [
                    'new_listings_today' => EquipmentListing::whereDate('created_at', $now->toDateString())->count(),
                    'new_users_today' => User::whereDate('created_at', $now->toDateString())->count(),
                    'new_listings_this_week' => EquipmentListing::where('created_at', '>=', $now->startOfWeek())->count(),
                    'new_users_this_week' => User::where('created_at', '>=', $now->startOfWeek())->count(),
                ],
                'status_breakdown' => [
                    'active' => EquipmentListing::where('status', 'active')->count(),
                    'draft' => EquipmentListing::where('status', 'draft')->count(),
                    'pending' => EquipmentListing::where('status', 'pending')->count(),
                    'sold' => EquipmentListing::where('status', 'sold')->count(),
                    'archived' => EquipmentListing::where('status', 'archived')->count(),
                ],
                'growth' => [
                    'listings_growth' => [
                        'current_month' => EquipmentListing::where('created_at', '>=', $now->startOfMonth())->count(),
                        'last_month' => EquipmentListing::whereBetween('created_at', [
                            $lastMonth->startOfMonth(),
                            $lastMonth->endOfMonth()
                        ])->count(),
                    ],
                    'users_growth' => [
                        'current_month' => User::where('created_at', '>=', $now->startOfMonth())->count(),
                        'last_month' => User::whereBetween('created_at', [
                            $lastMonth->startOfMonth(),
                            $lastMonth->endOfMonth()
                        ])->count(),
                    ],
                ],
            ];

            // Extended analytics if requested
            if ($request->get('extended', false)) {
                // Category analytics
                $categoryStats = EquipmentListing::join('equipment_categories', 'equipment_listings.category_id', '=', 'equipment_categories.id')
                    ->select('equipment_categories.name', \DB::raw('count(*) as count'))
                    ->where('equipment_listings.created_at', '>=', $startDate)
                    ->groupBy('equipment_categories.id', 'equipment_categories.name')
                    ->orderBy('count', 'desc')
                    ->limit(10)
                    ->get();

                // Top sellers
                $topSellers = EquipmentListing::join('user_profiles', 'equipment_listings.user_id', '=', 'user_profiles.user_id')
                    ->select('user_profiles.full_name', 'user_profiles.user_id', \DB::raw('count(*) as listing_count'))
                    ->where('equipment_listings.status', 'active')
                    ->where('equipment_listings.created_at', '>=', $startDate)
                    ->groupBy('user_profiles.user_id', 'user_profiles.full_name')
                    ->orderBy('listing_count', 'desc')
                    ->limit(10)
                    ->get();

                // Recent activity
                $recentUsers = User::with('profile')
                    ->latest()
                    ->limit(5)
                    ->get()
                    ->map(function ($user) {
                        return [
                            'id' => $user->id,
                            'name' => $user->profile->full_name ?? $user->name,
                            'email' => $user->email,
                            'joined' => $user->created_at->diffForHumans(),
                            'is_verified' => $user->profile->is_verified ?? false,
                        ];
                    });

                $recentListings = EquipmentListing::with(['category', 'seller'])
                    ->latest()
                    ->limit(5)
                    ->get()
                    ->map(function ($listing) {
                        return [
                            'id' => $listing->id,
                            'title' => $listing->title,
                            'category' => $listing->category->name ?? 'Uncategorized',
                            'seller' => $listing->seller->full_name ?? 'Unknown',
                            'price' => $listing->price,
                            'status' => $listing->status,
                            'created' => $listing->created_at->diffForHumans(),
                        ];
                    });

                // Performance indicators
                $totalViews = EquipmentListing::where('created_at', '>=', $startDate)
                    ->sum('view_count');
                $activeUsers = User::where('last_login_at', '>=', $startDate)->count();
                $conversionRate = $analytics['overview']['total_users'] > 0 
                    ? round(($analytics['overview']['verified_users'] / $analytics['overview']['total_users']) * 100, 2)
                    : 0;

                // Add extended data
                $analytics['extended'] = [
                    'category_stats' => $categoryStats,
                    'top_sellers' => $topSellers,
                    'recent_activity' => [
                        'users' => $recentUsers,
                        'listings' => $recentListings,
                    ],
                    'performance_indicators' => [
                        'conversion_rate' => $conversionRate . '%',
                        'total_views' => number_format($totalViews),
                        'active_users' => $activeUsers,
                        'avg_listings_per_user' => $analytics['overview']['total_users'] > 0 
                            ? round($analytics['overview']['total_listings'] / $analytics['overview']['total_users'], 2)
                            : 0,
                    ]
                ];

                // Subscription analytics if available
                try {
                    $subscriptionStats = [
                        'total_subscriptions' => \DB::table('user_subscriptions')->where('status', 'active')->count(),
                        'revenue_this_month' => \DB::table('user_subscriptions')
                            ->join('subscription_plans', 'user_subscriptions.plan_id', '=', 'subscription_plans.id')
                            ->where('user_subscriptions.status', 'active')
                            ->where('user_subscriptions.created_at', '>=', $now->startOfMonth())
                            ->sum('subscription_plans.price'),
                    ];
                    $analytics['extended']['subscription_stats'] = $subscriptionStats;
                } catch (\Exception $e) {
                    // Subscription tables might not exist
                }
            }

            // Add time range info to response
            $analytics['meta'] = [
                'time_range' => $timeRange,
                'start_date' => $startDate->toDateString(),
                'end_date' => $now->toDateString(),
                'generated_at' => $now->toIso8601String(),
            ];

            return response()->json([
                'success' => true,
                'data' => $analytics,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch dashboard analytics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }