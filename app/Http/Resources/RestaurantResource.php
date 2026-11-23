<?php

namespace App\Http\Resources;

use App\Models\Restaurant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Restaurant */
class RestaurantResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'email' => $this->email,
            'phone' => $this->phone,
            'whatsapp_number' => $this->whatsapp_number,
            'tagline' => $this->tagline,
            'description' => $this->description,
            'currency' => $this->currency,
            'logo_url' => $this->logo_url,
            'banner_url' => $this->banner_url,
            'primary_color' => $this->primary_color,
            'secondary_color' => $this->secondary_color,
            'location' => [
                'address' => $this->address,
                'city' => $this->city,
                'country' => $this->country,
                'google_maps_url' => $this->google_maps_url,
                'latitude' => $this->latitude,
                'longitude' => $this->longitude,
            ],
            'social_links' => [
                'website' => $this->website_url,
                'facebook' => $this->facebook_url,
                'instagram' => $this->instagram_url,
                'tiktok' => $this->tiktok_url,
                'youtube' => $this->youtube_url,
                'x' => $this->x_url,
            ],
            'opening_hours' => $this->opening_hours,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
