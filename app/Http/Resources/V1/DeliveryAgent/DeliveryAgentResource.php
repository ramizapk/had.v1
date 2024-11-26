<?php

namespace App\Http\Resources\V1\DeliveryAgent;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
class DeliveryAgentResource extends JsonResource
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
            'phone' => $this->phone,
            'agent_no' => $this->agent_no,
            'avatar' => $this->avatar ? Storage::url($this->avatar) : null,
            'address' => $this->address,
            'balance' => $this->balance,
            'region' => $this->region,
            'status' => $this->status,
            'is_active' => $this->is_active,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'created_at' => $this->created_at->format('Y-m-d'),
            'updated_at' => $this->updated_at->format('Y-m-d'),
        ];
    }
}
