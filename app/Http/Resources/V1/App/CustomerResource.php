<?php

namespace App\Http\Resources\V1\App;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
class CustomerResource extends JsonResource
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
            'name' => $this->name,
            'phone_number' => $this->phone_number,
            'avatar' => $this->avatar ? Storage::url($this->avatar) : null,
            'is_active' => $this->isActive(),
            'is_suspended' => $this->isSuspended(),
            'wallet' => $this->wallet, // إضافة المحفظة
            'locations' => $this->whenLoaded('addresses', function () {
                return $this->addresses->reduce(function ($carry, $address) {
                    $carry[$address->is_default ? 'active' : 'other_locations'][] = [
                        'id' => $address->id,
                        'name' => $address->name,
                        'location' => $address->latitude . ',' . $address->longitude,
                        'location_name' => $address->getOriginal('location'),
                    ];
                    return $carry;
                }, ['active' => [], 'other_locations' => []]);
            }),
            'points' => [
                'total_points' => $this->whenLoaded('points', fn() => $this->points->points ?? 0),
                'sticker_25' => $this->whenLoaded('points', fn() => (bool) $this->points->sticker_25),
                'sticker_50' => $this->whenLoaded('points', fn() => (bool) $this->points->sticker_50),
                'sticker_75' => $this->whenLoaded('points', fn() => (bool) $this->points->sticker_75),
                'sticker_100' => $this->whenLoaded('points', fn() => (bool) $this->points->sticker_100),
                'last_reset_date' => $this->whenLoaded('points', fn() => $this->points->date_reset_last),
            ],
        ];
    }
}
