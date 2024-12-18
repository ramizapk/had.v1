<?php

namespace App\Http\Resources\V1\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
class VendorResource extends JsonResource
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
            'phone_one' => $this->phone_one,
            'phone_two' => $this->phone_two,
            'email' => $this->email,
            'icon' => $this->icon ? Storage::url($this->icon) : null,  // عرض الرابط الكامل للصورة
            'publish' => $this->publish,
            'direct_order' => $this->direct_order,
            'is_service_provider' => $this->is_service_provider,
            'address' => $this->address,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'section' => [
                'id' => $this->section->id,
                'name' => $this->section->name,
            ],
            'work_times' => WorkTimeResource::collection($this->workTimes),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
