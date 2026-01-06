<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EquipmentListingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'brand' => $this->brand,
            'model' => $this->model,
            'year' => $this->year,
            'condition' => $this->condition,
            'listing_type' => $this->listing_type,
            'price' => $this->price,
            'formatted_price' => $this->getFormattedPriceDisplay(),
            'currency' => $this->currency,
            'is_price_negotiable' => $this->is_price_negotiable,
            'is_poa' => $this->is_poa,
            // Lease-specific fields
            'lease_price_daily' => $this->lease_price_daily,
            'lease_price_weekly' => $this->lease_price_weekly,
            'lease_price_monthly' => $this->lease_price_monthly,
            'lease_minimum_period' => $this->lease_minimum_period,
            'lease_security_deposit' => $this->lease_security_deposit,
            'lease_maintenance_included' => $this->lease_maintenance_included,
            'lease_insurance_required' => $this->lease_insurance_required,
            'lease_operator_license_required' => $this->lease_operator_license_required,
            'lease_commercial_use_allowed' => $this->lease_commercial_use_allowed,
            'lease_terms' => $this->when($this->listing_type == 'lease', function () {
                return [
                    'daily_rate' => $this->lease_price_daily ? $this->currency . ' ' . number_format($this->lease_price_daily, 2) . '/day' : null,
                    'weekly_rate' => $this->lease_price_weekly ? $this->currency . ' ' . number_format($this->lease_price_weekly, 2) . '/week' : null,
                    'monthly_rate' => $this->lease_price_monthly ? $this->currency . ' ' . number_format($this->lease_price_monthly, 2) . '/month' : null,
                    'minimum_period' => $this->lease_minimum_period ? $this->lease_minimum_period . ' days' : null,
                    'security_deposit' => $this->lease_security_deposit ? $this->currency . ' ' . number_format($this->lease_security_deposit, 2) : null,
                    'maintenance_included' => $this->lease_maintenance_included,
                    'insurance_required' => $this->lease_insurance_required,
                    'operator_license_required' => $this->lease_operator_license_required,
                    'commercial_use_allowed' => $this->lease_commercial_use_allowed,
                ];
            }),
            'specifications' => $this->specifications,
            'features' => $this->features,
            'location_state' => $this->location_state,
            'location_city' => $this->location_city,
            'location_address' => $this->location_address,
            'location' => [
                'state' => $this->location_state,
                'city' => $this->location_city,
                'address' => $this->hide_address ? null : $this->location_address,
            ],
            'hide_address' => $this->hide_address,
            'delivery_available' => $this->delivery_available,
            'delivery_radius' => $this->delivery_radius,
            'delivery_fee' => $this->delivery_fee,
            'delivery' => [
                'available' => $this->delivery_available,
                'radius' => $this->delivery_radius,
                'fee' => $this->delivery_fee,
            ],
            'contact_phone' => $this->contact_phone,
            'contact_email' => $this->contact_email,
            'contact_whatsapp' => $this->contact_whatsapp,
            'contact_methods' => $this->contact_methods,
            'availability_hours' => $this->availability_hours,
            'contact' => [
                'phone' => $this->contact_phone,
                'email' => $this->contact_email,
                'whatsapp' => $this->contact_whatsapp,
                'methods' => $this->contact_methods,
                'availability_hours' => $this->availability_hours,
            ],
            'allows_inspection' => $this->allows_inspection,
            'allows_test_drive' => $this->allows_test_drive,
            'inspection' => [
                'allows_inspection' => $this->allows_inspection,
                'allows_test_drive' => $this->allows_test_drive,
            ],
            'status' => $this->status,
            'next_available_date' => $this->next_available_date,
            'is_featured' => $this->is_featured,
            'is_verified' => $this->is_verified,
            'view_count' => $this->view_count,
            'inquiry_count' => $this->inquiry_count,
            'images' => $this->images,
            'tags' => $this->tags,
            'published_at' => $this->published_at,
            'expires_at' => $this->expires_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'category' => $this->whenLoaded('category', function () {
                return [
                    'id' => $this->category->id,
                    'name' => $this->category->name,
                    'slug' => $this->category->slug,
                ];
            }),
            'seller' => $this->whenLoaded('seller', function () {
                $seller = $this->seller;
                $profile = $seller->profile;
                $sellerProfile = $seller->sellerProfile;

                return [
                    'id' => $seller->id,
                    'seller_profile_id' => $sellerProfile?->id,
                    'name' => $profile?->full_name ?? $seller->name,
                    'full_name' => $profile?->full_name ?? $seller->name,
                    'company_name' => $profile?->company_name,
                    'businessType' => $sellerProfile?->business_name ?? $profile?->company_name,
                    'avatar' => $profile?->avatar_url,
                    'phone' => $profile?->phone,
                    'email' => $seller->email,
                    'location' => $sellerProfile?->location ?? ($profile ? "{$profile->city}, {$profile->state}" : null),
                    'isVerified' => $profile?->is_verified || $sellerProfile?->isVerified() || false,
                    'verification_status' => $sellerProfile?->verification_status ?? 'pending',
                    'rating' => $sellerProfile?->rating ?? 0,
                    'reviewCount' => $sellerProfile?->review_count ?? 0,
                    'stats' => [
                        'totalListings' => $sellerProfile?->total_listings ?? $seller->listings_count ?? 0,
                        'totalSales' => $seller->sales_count ?? 0,
                        'responseTime' => $sellerProfile?->response_time ?? 'N/A',
                        'joinedDate' => $seller->created_at ? $seller->created_at->format('M Y') : 'N/A',
                        'lastSeen' => $seller->updated_at ? $seller->updated_at->diffForHumans() : 'Recently',
                    ],
                ];
            }),
        ];
    }
}
