<?php

namespace App\Http\Resources\V1\Admin;

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
            'price' => $this->price,
            'start_date' => $this->start_date,  // تنسيق التاريخ
            'end_date' => $this->end_date,
            'placement' => $this->placement,
            'status' => $this->status,
            'section' => $this->section ? $this->section->name : null,  // إذا كان القسم موجوداً
            'vendor' => $this->vendor ? $this->vendor->name : null,  // إذا كان البائع موجوداً
            'product' => $this->product ? $this->product->name : null,  // إذا كان المنتج موجوداً
            'target_link' => $this->target_link,
            'created_by' => $this->createdBy ? $this->createdBy->name : null,
            'updated_by' => $this->updatedBy ? $this->updatedBy->name : null,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
