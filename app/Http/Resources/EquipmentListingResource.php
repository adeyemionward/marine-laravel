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
            'price' => $this->price,
            'formatted_price' => $this->formatted_price,
            'currency' => $this->currency,
            'is_price_negotiable' => $this->is_price_negotiable,
            'is_poa' => $this->is_poa,
            'specifications' => $this->specifications,
            'features' => $this->features,
            'location' => [
                'state' => $this->location_state,
                'city' => $this->location_city,
                'address' => $this->hide_address ? null : $this->location_address,
            ],
            'delivery' => [
                'available' => $this->delivery_available,
                'radius' => $this->delivery_radius,
                'fee' => $this->delivery_fee,
            ],
            'contact' => [
                'phone' => $this->contact_phone,
                'email' => $this->contact_email,
                'whatsapp' => $this->contact_whatsapp,
                'methods' => $this->contact_methods,
                'availability_hours' => $this->availability_hours,
            ],
            'inspection' => [
                'allows_inspection' => $this->allows_inspection,
                'allows_test_drive' => $this->allows_test_drive,
            ],
            'status' => $this->status,
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
                return [
                    'id' => $this->seller->id,
                    'full_name' => $this->seller->full_name,
                    'company_name' => $this->seller->company_name,
                    'is_verified' => $this->seller->is_verified,
                ];
            }),
        ];
    }
}
