<?php

namespace App\Http\Resources\V1\App;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
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
            'vendor' => new VendorResource($this->vendor),
            'address' => [
                'name' => $this->address->name,
                'location' => $this->address->location,
                'latitude' => $this->address->latitude,
                'longitude' => $this->address->longitude,
            ],
            'delivery_agent' => $this->deliveryAgent ? [
                'id' => $this->deliveryAgent->id,
                'name' => $this->deliveryAgent->name,
                'phone' => $this->deliveryAgent->phone,
                'avatar' => $this->deliveryAgent->avatar ? url($this->deliveryAgent->avatar) : null,
            ] : null, // عرض بيانات الوكيل إذا كانت موجودة
            'is_coupon' => $this->is_coupon,
            'used_coupon' => $this->used_coupon,
            'payment_method' => $this->payment_method,
            'total_price' => $this->total_price,
            'delivery_fee' => $this->delivery_fee,
            'final_price' => $this->final_price,
            'notes' => $this->notes,
            'is_returnable' => $this->is_returnable,
            'distance' => $this->distance,
            'status' => $this->status,
            'products' => OrderItemResource::collection($this->orderItems),
        ];
    }
}
