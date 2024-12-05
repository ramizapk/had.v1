<?php

namespace App\Http\Controllers\Api\Customers;

use App\Http\Controllers\Controller;
use App\Models\Customisation;
use App\Models\CustomProduct;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemCustomisation;
use App\Models\ProductItem;
use App\Models\ReturnImage;
use App\Models\ReturnItem;
use App\Models\Returns;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Models\Vendor;
use App\Models\Product;
use App\Models\Address;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;
class OrdersController extends Controller
{
    use ApiResponse;

    /**
     * حساب المسافة والوقت وسعر التوصيل بين الفيندور والمستخدم
     */
    public function calculateDistanceAndDeliveryFee(Request $request)
    {
        // التحقق من بيانات المدخلات
        $request->validate([
            'vendor_id' => 'required|exists:vendors,id',
        ]);

        // الحصول على العنوان الافتراضي للمستخدم
        $address = Address::where('customer_id', auth()->id())
            ->where('is_default', true)
            ->first();

        if (!$address) {
            return $this->errorResponse('No default address found for the customer', 400);
        }

        // الحصول على الفيندور
        $vendor = Vendor::find($request->vendor_id);

        // حساب المسافة والتكلفة
        $deliveryDetails = $address->calculateDeliveryDistanceAndTime($vendor);

        // حساب تكلفة التوصيل
        $deliveryFee = $this->calculateDeliveryFee($deliveryDetails['distance_km']);
        $couponDiscount = 0;
        // إرجاع الرد
        return $this->successResponse([
            'distance' => $deliveryDetails['distance_km'],
            'estimated_time' => $deliveryDetails['estimated_time_minutes'],
            'delivery_fee' => $deliveryFee,
        ], 'Delivery details calculated successfully.');
    }

    /**
     * التحقق من بيانات الطلب
     */

    public function checkOrder(Request $request)
    {
        return DB::transaction(function () use ($request) {
            // التحقق من البيانات المدخلة
            $request->validate([
                'vendor_id' => 'required|exists:vendors,id',
                'notes' => 'nullable|string',
                'is_coupon' => 'nullable|boolean',
                'used_coupon' => 'nullable|integer|in:25,50,75,100',
                'order_items' => 'required|array',
                'order_items.*.product_item_id' => 'required|exists:product_items,id',
                'order_items.*.quantity' => 'required|integer|min:1',
                'order_items.*.customisations' => 'nullable|array',
                'order_items.*.customisations.*.customisation_id' => 'required|exists:customisations,id',
                'order_items.*.customisations.*.items' => 'nullable|array',
            ]);

            $vendor = Vendor::find($request->vendor_id);
            $customer = auth()->user();
            $isReturnable = $vendor->section && $vendor->section->returnable;
            $address = Address::where('customer_id', $customer->id)
                ->where('is_default', true)
                ->first();

            if (!$address) {
                return $this->errorResponse('No default address found for the customer', 400);
            }

            // حساب المسافة والتكلفة
            $deliveryDetails = $address->calculateDeliveryDistanceAndTime($vendor);

            // التحقق من توفر المنتجات وحساب السعر الإجمالي
            $totalPrice = 0;
            $orderItems = [];

            foreach ($request->order_items as $item) {
                $product = ProductItem::find($item['product_item_id']);
                if (!$product || !$product->publish) {
                    return $this->errorResponse('Product unavailable or not active: ' . ($product->name ?? 'Unknown Product'), 400);

                }

                $unitPrice = $product->price;
                $itemPrice = $product->active_price * $item['quantity'];
                $totalPrice += $itemPrice;

                $customisations = [];
                if (isset($item['customisations'])) {
                    foreach ($item['customisations'] as $customisation) {
                        $customisationModel = Customisation::find($customisation['customisation_id']);
                        if ($customisationModel) {
                            $customisationItems = [];
                            foreach ($customisation['items'] as $customisationItem) {
                                $customProduct = CustomProduct::find($customisationItem['id']);
                                if ($customProduct) {
                                    $customisationItems[] = [
                                        'id' => $customProduct->id,
                                        'name' => $customProduct->name,
                                        'price' => $customProduct->price,
                                        'quantity' => $customisationItem['quantity'],
                                        'total_price' => $customProduct->price * $customisationItem['quantity'],
                                    ];
                                    $totalPrice += $customProduct->price * $customisationItem['quantity'];
                                }
                            }

                            $customisations[] = [
                                'customisation_id' => $customisation['customisation_id'],
                                'customisation_name' => $customisationModel->name,
                                'items' => $customisationItems,
                            ];
                        }
                    }
                }

                $orderItems[] = [
                    'product_item_id' => $item['product_item_id'],
                    'quantity' => $item['quantity'],
                    'unitPrice' => $unitPrice,
                    'is_discount' => $product->has_active_discount,
                    'unit_discount_price' => $unitPrice - $product->active_price,
                    'unit_price_after_discount' => $product->active_price,
                    'total_price' => $itemPrice,
                    'customisations' => $customisations,
                ];
            }

            $deliveryFee = $this->calculateDeliveryFee($deliveryDetails['distance_km']);
            $totalPrice += $deliveryFee;
            if ($request->used_coupon) {
                if (!$request->is_coupon) {
                    return $this->errorResponse('Invalid request: is_coupon must be true when used_coupon is provided.', 400);
                }

                $points = $customer->points;
                if (!$points) {
                    return $this->errorResponse('The customer has no points data', 400);
                }

                $pointsField = 'sticker_' . $request->used_coupon;
                if (!isset($points->{$pointsField}) || !$points->{$pointsField}) {
                    return $this->errorResponse('The customer does not have this coupon', 400);
                }

                $couponDiscount = ($deliveryFee * $request->used_coupon) / 100;
                $deliveryFee -= $couponDiscount;
                $totalPrice += max($deliveryFee, 0);

                // تحديث النقاط
                $points->{$pointsField} = false;
                $points->points -= $request->used_coupon;
                $points->save();
            }

            if ($request->payment_method === 'wallet') {
                if ($customer->wallet < $totalPrice) {

                    return $this->errorResponse('Insufficient wallet balance for the order', 400);
                }

                // خصم من المحفظة
                $customer->wallet -= $totalPrice;
                $customer->save();
            }

            // إنشاء الطلب وحفظ التفاصيل
            $order = Order::create([
                'customer_id' => $customer->id,
                'vendor_id' => $request->vendor_id,
                'address_id' => $address->id,
                'status' => 'pending',
                'total_price' => $totalPrice - $deliveryFee,
                'delivery_fee' => $deliveryFee,
                'is_coupon' => $request->is_coupon ? $request->is_coupon : false,
                'used_coupon' => $request->used_coupon ? strval($request->used_coupon) : null,
                'payment_method' => $request->payment_method,
                'payment_status' => $request->payment_method === 'wallet' ? 'paid' : 'pending',
                'final_price' => $totalPrice,
                'distance' => $deliveryDetails['distance_km'],
                'notes' => $request->notes,
                'is_returnable' => $isReturnable,
            ]);

            foreach ($orderItems as $item) {
                $orderItem = OrderItem::create([
                    'order_id' => $order->id,
                    'product_item_id' => $item['product_item_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unitPrice'],
                    'total_price' => $item['total_price'],
                ]);

                foreach ($item['customisations'] as $customisation) {
                    foreach ($customisation['items'] as $customisationItem) {
                        OrderItemCustomisation::create([
                            'order_item_id' => $orderItem->id,
                            'customisation_id' => $customisation['customisation_id'],
                            'custom_product_id' => $customisationItem['id'],
                            'quantity' => $customisationItem['quantity'],
                            'unit_price' => $customisationItem['price'],
                            'total_price' => $customisationItem['total_price'],
                        ]);
                    }
                }
            }

            return $this->successResponse([
                'order_id' => $order->id,
                'total_price' => $totalPrice,
                'wallet_balance' => $customer->wallet,
                'order_items' => $orderItems,
            ], 'Order created successfully.');
        });
    }


    /**
     * حساب تكلفة التوصيل بناءً على المسافة بالكيلومتر
     */
    private function calculateDeliveryFee($distanceKm)
    {
        $baseFee = 500; // سعر أساسي للتوصيل
        $perKmFee = 100; // تكلفة إضافية لكل كيلو متر بعد الـ 3 كيلومترات
        $freeDistance = 3; // المسافة المجانية (3 كيلومترات)

        if ($distanceKm <= $freeDistance) {
            // إذا كانت المسافة أقل من أو تساوي 3 كيلومترات
            return $baseFee;
        }

        // إذا كانت المسافة أكبر من 3 كيلومترات
        $extraDistance = $distanceKm - $freeDistance;
        return $baseFee + ($extraDistance * $perKmFee);
    }


    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 10);
        $orders = Order::where('customer_id', auth()->id())
            ->with([
                'orderItems.productItem.product.images',
                'orderItems.customisations.customisation',
                'orderItems.customisations.customProduct'
            ])
            ->paginate($perPage);

        $formattedOrders = $orders->getCollection()->map(function ($order) {
            $products = $order->orderItems->groupBy('product_item_id')->map(function ($items) {
                $productItem = $items->first()->productItem;
                $product = $productItem->product;

                return [
                    'id' => $product->id,
                    'name' => $product->product_name,
                    'image_url' => $product->images->where('is_default', true)->first()?->img_url
                        ? url($product->images->where('is_default', true)->first()?->img_url)
                        : ($product->images->first()?->img_url ? url($product->images->first()?->img_url) : null),
                    'items' => $items->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'product_item_id' => $item->product_item_id,
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
                                    })->toArray()
                                ];
                            })->values()->toArray()
                        ];
                    })->toArray()
                ];
            })->values()->toArray();

            return [
                'id' => $order->id,
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
                    'name' => $order->address->name,
                    'location' => $order->address->location,
                    'latitude' => $order->address->latitude,
                    'longitude' => $order->address->longitude,
                ],
                'delivery_agent' => $order->deliveryAgent ? [
                    'id' => $order->deliveryAgent->id,
                    'name' => $order->deliveryAgent->name,
                    'phone' => $order->deliveryAgent->phone,
                    'avatar' => $order->deliveryAgent->avatar ? url($order->deliveryAgent->avatar) : null,
                ] : null,
                'is_coupon' => $order->is_coupon,
                'used_coupon' => $order->used_coupon,
                'payment_method' => $order->payment_method,
                'total_price' => $order->total_price,
                'delivery_fee' => $order->delivery_fee,
                'final_price' => $order->final_price,
                'notes' => $order->notes,
                'is_returnable' => $order->is_returnable,
                'distance' => $order->distance,
                'status' => $order->status,
                'products' => $products,
            ];
        });

        // إضافة بيانات الباجينيشن إلى الاستجابة
        return $this->successResponse([
            'data' => $formattedOrders,
            'pagination' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
            ],
        ], 'Orders retrieved successfully');
    }



    public function submitReturn(Request $request)
    {
        return DB::transaction(function () use ($request) {
            $request->validate([
                'order_id' => 'required|exists:orders,id',
                'return_items' => 'required|array',
                'return_items.*.order_item_id' => 'required|exists:order_items,id',
                'return_items.*.quantity' => 'required|integer|min:1',
                'images' => 'nullable|array|max:5', // يمكن رفع حتى 5 صور
                'images.*' => 'nullable|image|max:2048', // الحد الأقصى للصورة 2 ميجابايت
                'reason' => 'required|string|max:500',
            ]);

            $order = Order::with(['orderItems.customisations.customProduct'])->findOrFail($request->order_id);
            $customer = auth()->user();

            // تحقق من أن الطلب ينتمي إلى العميل
            if ($order->customer_id !== $customer->id) {
                return $this->errorResponse('You do not have permission to return this order.', 403);
            }

            // حساب تكلفة التوصيل بناءً على المسافة
            $deliveryFee = $this->calculateDeliveryFee($order->distance);

            // إنشاء المرتجع
            $returnOrder = Returns::create([
                'order_id' => $order->id,
                'delivery_agent_id' => null, // يتم التعيين لاحقًا
                'delivery_fee' => $deliveryFee,
                'distance' => $order->distance,
                'status' => 'pending',
                'reason' => $request->reason,
            ]);

            // حفظ المنتجات المرتجعة
            foreach ($request->return_items as $item) {
                $orderItem = $order->orderItems->where('id', $item['order_item_id'])->first();
                if (!$orderItem || $item['quantity'] > $orderItem->quantity) {
                    return $this->errorResponse('Invalid item or quantity exceeds the ordered quantity.', 400);
                }

                ReturnItem::create([
                    'return_id' => $returnOrder->id,
                    'order_item_id' => $item['order_item_id'],
                    'quantity' => $item['quantity'],
                ]);
            }

            // حفظ الصور المرتبطة بالمرتجع
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $path = $image->store('returns_images', 'public');
                    ReturnImage::create([
                        'returns_id' => $returnOrder->id,
                        'image_url' => $path,
                    ]);
                }
            }

            return $this->successResponse([
                'return_id' => $returnOrder->id,
                'delivery_fee' => $deliveryFee,
            ], 'Return request submitted successfully.');
        });
    }


    public function getReturns(Request $request)
    {
        $perPage = $request->get('per_page', 10);
        $returns = Returns::whereHas('order', function ($query) {
            $query->where('customer_id', auth()->id());
        })
            ->with(['order', 'returnItems.orderItem.productItem.product.images', 'returnImages'])
            ->paginate($perPage);

        $formattedReturns = $returns->getCollection()->map(function ($returnOrder) {
            return [
                'return_id' => $returnOrder->id,
                'order_id' => $returnOrder->order->id,
                'delivery_fee' => $returnOrder->delivery_fee,
                'status' => $returnOrder->status,
                'reason' => $returnOrder->reason,
                'images' => $returnOrder->returnImages->map(function ($image) {
                    return url('storage/' . $image->image_url);
                }),
                'items' => $returnOrder->returnItems->map(function ($returnItem) {
                    $orderItem = $returnItem->orderItem;
                    $product = $orderItem->productItem->product;

                    return [
                        'order_item_id' => $orderItem->id,
                        'product_name' => $product->product_name,
                        'product_image' => $product->images->where('is_default', true)->first()?->img_url
                            ? url($product->images->where('is_default', true)->first()->img_url)
                            : ($product->images->first()?->img_url ? url($product->images->first()->img_url) : null),
                        'quantity' => $returnItem->quantity,
                        'unit_price' => $orderItem->unit_price,
                        'total_price' => $orderItem->unit_price * $returnItem->quantity,
                        'customisations' => $orderItem->customisations->groupBy('customisation_id')->map(function ($group) {
                            $customisation = $group->first()->customisation;
                            return [
                                'id' => $customisation->id,
                                'name' => $customisation->name,
                                'items' => $group->map(function ($item) {
                                    return [
                                        'id' => $item->customProduct->id,
                                        'name' => $item->customProduct->name,
                                        'price' => $item->customProduct->price,
                                    ];
                                }),
                            ];
                        })->values(),
                    ];
                }),
            ];
        });

        return $this->successResponse([
            'data' => $formattedReturns,
            'pagination' => [
                'current_page' => $returns->currentPage(),
                'last_page' => $returns->lastPage(),
                'per_page' => $returns->perPage(),
                'total' => $returns->total(),
            ],
        ], 'Returns retrieved successfully.');
    }

}
