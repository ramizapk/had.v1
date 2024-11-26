<?php

namespace App\Http\Resources\V1\App;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
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
            'return_able' => $this->return_able,
            'order' => $this->order,
            'section_id' => $this->section_id,
            'vendor_id' => $this->pivot->vendor_id,
            'is_offer' => $this->is_offer,
        ];
    }
}
