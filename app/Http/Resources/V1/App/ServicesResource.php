<?php

namespace App\Http\Resources\V1\App;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
class ServicesResource extends JsonResource
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
            'whatsapp_link' => $this->whatsapp_link,
            'service_type_id' => $this->service_type_id,
        ];

    }
}
