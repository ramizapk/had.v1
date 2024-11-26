<?php

namespace App\Http\Resources\V1\App;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ProductResource extends JsonResource
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
            'product_name' => $this->product_name,
            'description' => $this->description,
            'is_offer' => $this->is_offer,
            'offer_price' => $this->is_offer ? $this->offer_price : null,
            'images' => $this->images->map(function ($image) {
                return [
                    'id' => $image->id,
                    'img_url' => $image->img_url ? Storage::url($image->img_url) : null,
                    'is_default' => $image->is_default,
                ];
            }),
            'options' => $this->items->map(function ($item) {
                if ($this->is_offer) {
                    // إذا كان المنتج عبارة عن عرض
                    return [
                        'id' => $item->id,
                        'name' => $item->name,
                        'quantity' => $item->description, // عرض الوصف ككمية
                    ];
                } else {
                    // إذا لم يكن المنتج عرضًا
                    return [
                        'id' => $item->id,
                        'name' => $item->name,
                        'description' => $item->description,
                        'price' => $item->price,
                        'discount_price' => $item->active_price, // السعر النهائي بعد الخصم
                        'has_active_discount' => $item->has_active_discount, // هل الخصم نشط؟
                        'customisations' => $item->customisations->map(function ($customisationItem) {
                        return [
                            'customisation_id' => $customisationItem->customisation_id,
                            'customisation_name' => $customisationItem->customisation->name,
                            'is_multi_select' => $customisationItem->customisation->is_multi_select,
                            'items' => json_decode($customisationItem->items, true),
                        ];
                    }),
                    ];
                }
            }),
        ];
    }
}
