<?php

namespace App\Http\Resources\V1\App;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OfferResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->product_name,
            'image_url' => $this->images->where('is_default', true)->first()?->img_url
                ? url($this->images->where('is_default', true)->first()?->img_url)
                : ($this->images->first()?->img_url ? url($this->images->first()?->img_url) : null),
            'items' => $this->items->map(function ($offerItem) {
                return [
                    'id' => $offerItem->id,
                    'name' => $offerItem->name,
                    'description' => $offerItem->description,
                    'quantity' => $offerItem->quantity,
                ];
            })->toArray(),
        ];
    }
}
