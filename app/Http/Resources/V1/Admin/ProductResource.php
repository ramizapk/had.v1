<?php

namespace App\Http\Resources\V1\Admin;

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
            'publish' => $this->publish,
            'is_offer' => $this->is_offer,
            'offer_price' => $this->is_offer ? $this->offer_price : null,
            'vendor' => new VendorResource($this->vendor),  // تغليف بيانات الفيندور
            'category' => new CategoryResource($this->category),  // تغليف بيانات الفئة
            'createdBy' => $this->created_by ? new UserResource($this->createdBy) : null,  // تغليف بيانات من أنشأ العنصر
            'updatedBy' => $this->updated_by ? new UserResource($this->updatedBy) : null,
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
                        'publish' => $item->publish,
                        'discount_type' => $item->discount_type,
                        'discount_amount' => $item->discount_amount,
                        'discount_percent' => $item->discount_percent,
                        'date_from' => $item->date_from,
                        'date_to' => $item->date_to,
                        'discount_price' => $item->active_price, // السعر النهائي بعد الخصم
                        'has_active_discount' => $item->has_active_discount, // هل الخصم نشط؟
                        'createdBy' => $this->created_by ? new UserResource($this->createdBy) : null,  // تغليف بيانات من أنشأ العنصر
                        'updatedBy' => $this->updated_by ? new UserResource($this->updatedBy) : null,
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
