<?php
namespace App\Services;

use App\Models\Returns;

class ReturnService
{


    public function formatReturns($returns)
    {
        return $returns->getCollection()->map(function ($return) {
            $products = $return->returnItems->map(function ($item) {
                if ($item->orderItem->is_offer) {
                    $offer = $item->orderItem->offer;
                    return [
                        'returnItem_id' => $item->id,
                        'orderItem_id' => $item->orderItem->id,
                        'offer_id' => $offer->id,
                        'name' => $offer->product_name,
                        'image_url' => $offer->images->where('is_default', true)->first()?->img_url ? url($offer->images->where('is_default', true)->first()->img_url) : ($offer->images->first()?->img_url ? url($offer->images->first()->img_url) : null),
                        'quantity' => $item->quantity,
                        'unit_price' => $item->unit_price,
                        'total_price' => $item->total_price,
                        'items' => $offer->items->map(function ($offerItem) {
                            return [
                                'id' => $offerItem->id,
                                'name' => $offerItem->name,
                                'description' => $offerItem->description,
                                'quantity' => $offerItem->quantity,
                            ];
                        })->toArray(),
                    ];
                } else {
                    $productItem = $item->orderItem->productItem;
                    $product = $productItem->product;
                    return [
                        'id' => $product->id,
                        'name' => $product->product_name,
                        'image_url' => $product->images->where('is_default', true)->first()?->img_url ? url($product->images->where('is_default', true)->first()?->img_url) : ($product->images->first()?->img_url ? url($product->images->first()?->img_url) : null),
                        'items' => [
                            [
                                'returnItem_id' => $item->id,
                                'orderItem_id' => $item->orderItem->id,
                                'product_item_id' => $productItem->id,
                                'name' => $productItem->name,
                                'quantity' => $item->quantity,
                                'unit_price' => $item->unit_price,
                                'total_customisation_price' => $item->total_customisation_price,
                                'total_price' => $item->total_price,
                                'customisations' => ($item->orderItem->customisations ?? collect())->isNotEmpty() ? $item->orderItem->customisations->groupBy('customisation_id')->map(function ($customisationGroup) {
                                    $customisation = $customisationGroup->first()->customisation;
                                    return [
                                        'id' => $customisation->id,
                                        'name' => $customisation->name,
                                        'items' => $customisationGroup->map(function ($customisationItem) {
                                            return [
                                                'id' => $customisationItem->id,
                                                'customisation_item_id' => $customisationItem->customProduct->id,
                                                'name' => $customisationItem->customProduct->name,
                                                'price' => $customisationItem->customProduct->price,
                                            ];
                                        })->toArray(),
                                    ];
                                })->values()->toArray() : [],
                            ]
                        ],
                    ];
                }
            })->groupBy('id')->map(function ($groupedItems) {
                $product = $groupedItems->first();
                $product['items'] = $groupedItems->pluck('items')->flatten(1)->toArray();
                return $product;
            })->values()->toArray();


            return [
                'id' => $return->id,
                'order_id' => $return->order_id,
                'reason' => $return->reason,
                'delivery_fee' => $return->delivery_fee,
                'return_price' => $return->return_price,
                'distance' => $return->distance,
                'status' => $return->status,
                'payment_method' => $return->payment_method,
                'payment_status' => $return->payment_status,
                'created_at' => $return->created_at,
                'updated_at' => $return->updated_at,
                'images' => $return->returnImages->map(function ($image) {
                    return [
                        'id' => $image->id,
                        'image_url' => $image->image_url,
                    ];
                }),

                'customer' => [
                    'id' => $return->customer->id,
                    'name' => $return->customer->name,
                    'phone_number' => $return->customer->phone_number,
                    'avatar' => $return->customer->avatar ? url($return->customer->avatar) : null,
                    'wallet' => $return->customer->wallet,
                    'is_active' => $return->customer->is_active,
                    'is_suspended' => $return->customer->is_suspended,
                ],

                'address' => [
                    'location' => $return->customer_location,
                    'latitude' => $return->customer_latitude,
                    'longitude' => $return->customer_longitude,
                ],
                'vendor' => [
                    'id' => $return->vendor->id,
                    'name' => $return->vendor->name,
                    'icon' => $return->vendor->icon ? url($return->vendor->icon) : null,
                    'address' => $return->vendor->address,
                    'phone_one' => $return->vendor->phone_one,
                    'phone_two' => $return->vendor->phone_two,
                    'email' => $return->vendor->email,
                    'latitude' => $return->vendor->latitude,
                    'longitude' => $return->vendor->longitude,
                ],
                'delivery_agent' => $return->deliveryAgent ? [
                    'id' => $return->deliveryAgent->id,
                    'name' => $return->deliveryAgent->name,
                    'phone' => $return->deliveryAgent->phone,
                    'avatar' => $return->deliveryAgent->avatar ? url($return->deliveryAgent->avatar) : null,
                ] : null,
                'products' => $products,
            ];
        });
    }

    public function formatReturn(Returns $return)
    {
        $products = $return->returnItems->map(function ($item) {
            if ($item->orderItem->is_offer) {
                $offer = $item->orderItem->offer;
                return [
                    'returnItem_id' => $item->id,
                    'orderItem_id' => $item->orderItem->id,
                    'offer_id' => $offer->id,
                    'name' => $offer->product_name,
                    'image_url' => $offer->images->where('is_default', true)->first()?->img_url
                        ? url($offer->images->where('is_default', true)->first()->img_url)
                        : ($offer->images->first()?->img_url
                            ? url($offer->images->first()->img_url)
                            : null),
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'total_price' => $item->total_price,
                    'items' => $offer->items->map(function ($offerItem) {
                        return [
                            'id' => $offerItem->id,
                            'name' => $offerItem->name,
                            'description' => $offerItem->description,
                            'quantity' => $offerItem->quantity,
                        ];
                    })->toArray(),
                ];
            } else {
                $productItem = $item->orderItem->productItem;
                $product = $productItem->product;
                return [
                    'id' => $product->id,
                    'name' => $product->product_name,
                    'image_url' => $product->images->where('is_default', true)->first()?->img_url
                        ? url($product->images->where('is_default', true)->first()->img_url)
                        : ($product->images->first()?->img_url
                            ? url($product->images->first()->img_url)
                            : null),
                    'items' => [
                        [
                            'returnItem_id' => $item->id,
                            'orderItem_id' => $item->orderItem->id,
                            'product_item_id' => $productItem->id,
                            'name' => $productItem->name,
                            'quantity' => $item->quantity,
                            'unit_price' => $item->unit_price,
                            'total_customisation_price' => $item->total_customisation_price,
                            'total_price' => $item->total_price,
                            'customisations' => ($item->orderItem->customisations ?? collect())->isNotEmpty()
                                ? $item->orderItem->customisations->groupBy('customisation_id')->map(function ($customisationGroup) {
                                    $customisation = $customisationGroup->first()->customisation;
                                    return [
                                        'id' => $customisation->id,
                                        'name' => $customisation->name,
                                        'items' => $customisationGroup->map(function ($customisationItem) {
                                            return [
                                                'id' => $customisationItem->id,
                                                'customisation_item_id' => $customisationItem->customProduct->id,
                                                'name' => $customisationItem->customProduct->name,
                                                'price' => $customisationItem->customProduct->price,
                                            ];
                                        })->toArray(),
                                    ];
                                })->values()->toArray()
                                : [],
                        ]
                    ],
                ];
            }
        })->groupBy('id')->map(function ($groupedItems) {
            $product = $groupedItems->first();
            $product['items'] = $groupedItems->pluck('items')->flatten(1)->toArray();
            return $product;
        })->values()->toArray();

        return [
            'id' => $return->id,
            'order_id' => $return->order_id,
            'reason' => $return->reason,
            'delivery_fee' => $return->delivery_fee,
            'return_price' => $return->return_price,
            'distance' => $return->distance,
            'status' => $return->status,
            'payment_method' => $return->payment_method,
            'payment_status' => $return->payment_status,
            'created_at' => $return->created_at,
            'updated_at' => $return->updated_at,
            'images' => $return->returnImages->map(function ($image) {
                return [
                    'id' => $image->id,
                    'image_url' => $image->image_url,
                ];
            }),
            'customer' => [
                'id' => $return->customer->id,
                'name' => $return->customer->name,
                'phone_number' => $return->customer->phone_number,
                'avatar' => $return->customer->avatar ? url($return->customer->avatar) : null,
                'wallet' => $return->customer->wallet,
                'is_active' => $return->customer->is_active,
                'is_suspended' => $return->customer->is_suspended,
            ],
            'address' => [
                'location' => $return->customer_location,
                'latitude' => $return->customer_latitude,
                'longitude' => $return->customer_longitude,
            ],
            'vendor' => [
                'id' => $return->vendor->id,
                'name' => $return->vendor->name,
                'icon' => $return->vendor->icon ? url($return->vendor->icon) : null,
                'address' => $return->vendor->address,
                'phone_one' => $return->vendor->phone_one,
                'phone_two' => $return->vendor->phone_two,
                'email' => $return->vendor->email,
                'latitude' => $return->vendor->latitude,
                'longitude' => $return->vendor->longitude,
            ],
            'delivery_agent' => $return->deliveryAgent ? [
                'id' => $return->deliveryAgent->id,
                'name' => $return->deliveryAgent->name,
                'phone' => $return->deliveryAgent->phone,
                'avatar' => $return->deliveryAgent->avatar ? url($return->deliveryAgent->avatar) : null,
            ] : null,
            'products' => $products,
        ];
    }

}
