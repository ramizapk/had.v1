<?php

namespace App\Http\Resources\V1\App;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        // إذا كان العنصر هو عرض
        if ($this->is_offer) {
            // إرجاع العرض باستخدام OfferResource
            return (new OfferResource($this->offer))->toArray($request);
        }

        // الحصول على المنتج المرتبط بالـ product_item
        $productItem = $this->productItem;
        $product = $productItem->product;

        // استرجاع صورة المنتج
        $imageUrl = $product->images->where('is_default', true)->first()?->img_url
            ? url($product->images->where('is_default', true)->first()->img_url)
            : ($product->images->first()?->img_url ? url($product->images->first()->img_url) : null);

        // تجميع العناصر المرتبطة بنفس المنتج باستخدام groupBy
        $itemsGrouped = $this->customisations->groupBy('product_item_id')->map(function ($customisationGroup) {
            $customisation = $customisationGroup->first()->customisation;
            return [
                'id' => $customisation->id,
                'name' => $customisation->name,
                'items' => $customisationGroup->map(function ($customisationItem) {
                    return [
                        'id' => $customisationItem->customProduct->id,
                        'name' => $customisationItem->customProduct->name,
                        'price' => $customisationItem->customProduct->price,
                    ];
                })->toArray(),
            ];
        })->values()->toArray();

        // تجميع جميع المنتجات بنفس الـ product_id
        return [
            'id' => $product->id,
            'name' => $product->product_name,
            'image_url' => $imageUrl,
            'items' => $this->collection->groupBy('product_id')->map(function ($groupedItems) {
                return $groupedItems->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'product_item_id' => $item->productItem->id,
                        'name' => $item->productItem->name,
                        'customisations' => $item->customisations->groupBy('customisation_id')->map(function ($customisationGroup) {
                            $customisation = $customisationGroup->first()->customisation;
                            return [
                                'id' => $customisation->id,
                                'name' => $customisation->name,
                                'items' => $customisationGroup->map(function ($customisationItem) {
                                    return [
                                        'id' => $customisationItem->customProduct->id,
                                        'name' => $customisationItem->customProduct->name,
                                        'price' => $customisationItem->customProduct->price,
                                    ];
                                })->toArray(),
                            ];
                        })->values()->toArray(),
                    ];
                });
            })->flatten(1)->toArray(),
        ];
    }
}
