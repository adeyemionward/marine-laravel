<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $isSeller = $this->isSeller();
        $roles = method_exists($this->resource, 'getRoleNames') ? $this->getRoleNames() : [];
        $sellerProfile = $this->relationLoaded('sellerProfile') ? $this->sellerProfile : null;

        // Debug logging
        \Log::info('UserResource Debug', [
            'user_id' => $this->id,
            'roles' => $roles,
            'is_seller' => $isSeller,
            'has_seller_profile' => $sellerProfile !== null,
            'seller_profile_loaded' => $this->relationLoaded('sellerProfile'),
        ]);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'email_verified_at' => $this->email_verified_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'active_status' => $this->active_status,
            'is_active' => $this->active_status == \App\Models\User::ACTIVE,
            'profile' => new UserProfileResource($this->whenLoaded('profile')),
            'role' => $this->whenLoaded('role', function () {
                return [
                    'id' => $this->role->id,
                    'name' => $this->role->name,
                    'display_name' => $this->role->display_name ?? $this->role->name,
                    'permissions' => $this->role->permissions ?? [],
                ];
            }),
            // Direct permissions (Spatie)
            'permissions' => $this->when(
                method_exists($this->resource, 'getAllPermissions'),
                fn() => $this->getAllPermissions()->pluck('name')
            ),
            // Role object with permissions (Spatie)
            'roles' => $roles,
            'is_seller' => $isSeller,
            'seller_profile' => $this->whenLoaded('sellerProfile'),
            'subscriptions' => $this->whenLoaded('subscriptions'),
            'subscription' => $this->when(
                $this->relationLoaded('subscriptions'),
                function() {
                    $activeSubscription = $this->subscriptions->where('status', 'active')
                        ->where('expires_at', '>', now())
                        ->first();

                    if ($activeSubscription) {
                        return [
                            'id' => $activeSubscription->id,
                            'plan_id' => $activeSubscription->plan_id,
                            'plan_name' => $activeSubscription->plan ? $activeSubscription->plan->name : null,
                            'status' => $activeSubscription->status,
                            'started_at' => $activeSubscription->started_at,
                            'expires_at' => $activeSubscription->expires_at,
                            'auto_renew' => $activeSubscription->auto_renew,
                        ];
                    }

                    return null;
                }
            ),
        ];
    }
}