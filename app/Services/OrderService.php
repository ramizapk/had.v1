<?php

namespace App\Services;

use App\Models\Order;

class OrderService
{
    public function formatOrders($orders)
    {
        return $orders->getCollection()->map(function ($order) {
            $products = $order->orderItems->map(function ($item) {
                if ($item->is_offer) {
                    $offer = $item->offer;
                    return [
                        'id' => $item->id,
                        'offer_id' => $offer->id,
                        'name' => $offer->product_name,
                        'image_url' => $offer->images->where('is_default', true)->first()?->img_url ? url($offer->images->where('is_default', true)->first()->img_url) : ($offer->images->first()?->img_url ? url($offer->images->first()->img_url) : null),
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
                    $productItem = $item->productItem;
                    $product = $productItem->product;
                    return [
                        'id' => $product->id,
                        'name' => $product->product_name,
                        'image_url' => $product->images->where('is_default', true)->first()?->img_url ? url($product->images->where('is_default', true)->first()?->img_url) : ($product->images->first()?->img_url ? url($product->images->first()?->img_url) : null),
                        'items' => [
                            [
                                'id' => $item->id,
                                'product_item_id' => $productItem->id,
                                'name' => $productItem->name,
                                'customisations' => $item->customisations->groupBy('customisation_id')->map(function ($customisationGroup) {
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
                                })->values()->toArray(),
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
                'id' => $order->id,
                'is_coupon' => $order->is_coupon,
                'used_coupon' => $order->used_coupon,
                'payment_method' => $order->payment_method,
                'payment_status' => $order->payment_status,
                'total_price' => $order->total_price,
                'delivery_fee' => $order->delivery_fee,
                'final_price' => $order->final_price,
                'notes' => $order->notes,
                'is_returnable' => $order->is_returnable,
                'distance' => $order->distance,
                'status' => $order->status,
                'created_at' => $order->created_at,
                'updated_at' => $order->updated_at,
                'vendor' => [
                    'id' => $order->vendor->id,
                    'name' => $order->vendor->name,
                    'icon' => $order->vendor->icon ? url($order->vendor->icon) : null,
                    'address' => $order->vendor->address,
                    'phone_one' => $order->vendor->phone_one,
                    'phone_two' => $order->vendor->phone_two,
                    'email' => $order->vendor->email,
                    'latitude' => $order->vendor->latitude,
                    'longitude' => $order->vendor->longitude,
                ],
                'address' => [
                    'location' => $order->customer_location,
                    'latitude' => $order->customer_latitude,
                    'longitude' => $order->customer_longitude,
                ],
                'delivery_agent' => $order->deliveryAgent ? [
                    'id' => $order->deliveryAgent->id,
                    'name' => $order->deliveryAgent->name,
                    'phone' => $order->deliveryAgent->phone,
                    'avatar' => $order->deliveryAgent->avatar ? url($order->deliveryAgent->avatar) : null,
                ] : null,
                'customer' => [
                    'id' => $order->customer->id,
                    'name' => $order->customer->name,
                    'phone_number' => $order->customer->phone_number,
                    'avatar' => $order->customer->avatar ? url($order->customer->avatar) : null,
                    'wallet' => $order->customer->wallet,
                    'is_active' => $order->customer->is_active,
                    'is_suspended' => $order->customer->is_suspended,
                ],
                'products' => $products,
            ];
        });
    }
    public function formatOrder(Order $order)
    {
        $products = $order->orderItems->map(function ($item) {
            if ($item->is_offer) {
                $offer = $item->offer;
                return [
                    'id' => $item->id,
                    'offer_id' => $offer->id,
                    'name' => $offer->product_name,
                    'image_url' => $offer->images->where('is_default', true)->first()?->img_url ? url($offer->images->where('is_default', true)->first()->img_url) : ($offer->images->first()?->img_url ? url($offer->images->first()?->img_url) : null),
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
                $productItem = $item->productItem;
                $product = $productItem->product;
                return [
                    'id' => $product->id,
                    'name' => $product->product_name,
                    'image_url' => $product->images->where('is_default', true)->first()?->img_url ? url($product->images->where('is_default', true)->first()?->img_url) : ($product->images->first()?->img_url ? url($product->images->first()?->img_url) : null),
                    'items' => [
                        [
                            'id' => $item->id,
                            'product_item_id' => $productItem->id,
                            'name' => $productItem->name,
                            'customisations' => $item->customisations->groupBy('customisation_id')->map(function ($customisationGroup) {
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
                            })->values()->toArray(),
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
            'id' => $order->id,
            'is_coupon' => $order->is_coupon,
            'used_coupon' => $order->used_coupon,
            'payment_method' => $order->payment_method,
            'payment_status' => $order->payment_status,
            'total_price' => $order->total_price,
            'delivery_fee' => $order->delivery_fee,
            'final_price' => $order->final_price,
            'notes' => $order->notes,
            'is_returnable' => $order->is_returnable,
            'distance' => $order->distance,
            'status' => $order->status,
            'created_at' => $order->created_at,
            'updated_at' => $order->updated_at,
            'vendor' => [
                'id' => $order->vendor->id,
                'name' => $order->vendor->name,
                'icon' => $order->vendor->icon ? url($order->vendor->icon) : null,
                'address' => $order->vendor->address,
                'phone_one' => $order->vendor->phone_one,
                'phone_two' => $order->vendor->phone_two,
                'email' => $order->vendor->email,
                'latitude' => $order->vendor->latitude,
                'longitude' => $order->vendor->longitude,
            ],
            'address' => [
                'location' => $order->customer_location,
                'latitude' => $order->customer_latitude,
                'longitude' => $order->customer_longitude,
            ],
            'delivery_agent' => $order->deliveryAgent ? [
                'id' => $order->deliveryAgent->id,
                'name' => $order->deliveryAgent->name,
                'phone' => $order->deliveryAgent->phone,
                'avatar' => $order->deliveryAgent->avatar ? url($order->deliveryAgent->avatar) : null,
            ] : null,
            'customer' => [
                'id' => $order->customer->id,
                'name' => $order->customer->name,
                'phone_number' => $order->customer->phone_number,
                'avatar' => $order->customer->avatar ? url($order->customer->avatar) : null,
                'wallet' => $order->customer->wallet,
                'is_active' => $order->customer->is_active,
                'is_suspended' => $order->customer->is_suspended,
            ],
            'products' => $products,
        ];
    }

}