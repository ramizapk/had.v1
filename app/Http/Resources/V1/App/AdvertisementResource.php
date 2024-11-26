<?php

namespace App\Http\Resources\V1\App;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
class AdvertisementResource extends JsonResource
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
            'description' => $this->description,
            'image' => $this->image ? Storage::url($this->image) : null,  // عرض الرابط الكامل للصورة
            'type' => $this->type,
            'vendor_id' => $this->vendor_id,
            'product_id' => $this->product_id,
            'target_link' => $this->target_link,
            'status' => $this->status,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'placement' => $this->placement,
            'section_id' => $this->section_id,
        ];
    }
}
