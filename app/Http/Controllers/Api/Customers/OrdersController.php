<?php

namespace App\Http\Controllers\Api\Customers;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\DirectOrderResource;
use App\Models\Customisation;
use App\Models\CustomProduct;
use App\Models\DirectOrder;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemCustomisation;
use App\Models\ProductItem;
use App\Models\ReturnImage;
use App\Models\ReturnItem;
use App\Models\Returns;
use App\Services\ReturnService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Models\Vendor;
use App\Models\Product;
use App\Models\Address;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;
use App\Services\OrderService;
use Illuminate\Support\Facades\Storage;
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
            'vendor_id' => 'nullable|exists:vendors,id',
            'vendor_lat' => 'nullable|numeric',
            'vendor_long' => 'nullable|numeric',
        ]);
        $vendor_lat = null;
        $vendor_long = null;
        // الحصول على العنوان الافتراضي للمستخدم
        $address = Address::where('customer_id', auth()->id())
            ->where('is_default', true)
            ->first();

        if (!$address) {
            return $this->errorResponse('No default address found for the customer', 400);
        }

        if (!$request->vendor_id && (!$request->vendor_lat && !$request->vendor_long)) {
            return $this->errorResponse('you must send the vendor data', 400);
        }

        // الحصول على الفيندور
        if ($request->vendor_id) {
            // البحث عن الفيندور باستخدام id
            $vendor = Vendor::find($request->vendor_id);

            // إذا لم يوجد الفيندور بناءً على id المرسل
            if (!$vendor) {
                return $this->errorResponse('Vendor not found', 404);
            }

            // إذا كان الفيندور موجودًا، نقوم بإضافة إحداثياته
            $vendor_lat = $vendor->latitude;
            $vendor_long = $vendor->longitude;
            $vendor_name = $vendor->name;
            $deliveryDetails = $address->calculateDeliveryDistanceAndTime($vendor);
        } else {
            // إذا لم يتم إرسال vendor_id، نأخذ الإحداثيات من المدخلات (إذا كانت موجودة)
            $vendor_lat = $request->vendor_lat;
            $vendor_long = $request->vendor_long;
            $vendor_name = $request->vendor_name;
            $deliveryDetails = $address->calculateDeliveryDistanceAndTime(null, $vendor_lat, $vendor_long);
        }

        // حساب رسوم التوصيل
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

    public function checkOrder(Request $request, OrderService $orderFormattingService)
    {
        return DB::transaction(function () use ($request, $orderFormattingService) {
            // التحقق من البيانات المدخلة
            $request->validate([
                'vendor_id' => 'required|exists:vendors,id',
                'notes' => 'nullable|string',
                'is_coupon' => 'nullable|boolean',
                'used_coupon' => 'nullable|integer|in:25,50,75,100',
                'order_items' => 'required|array',
                'order_items.*.product_item_id' => 'nullable|exists:product_items,id',
                'order_items.*.offer_id' => 'nullable|exists:products,id',
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
                if (isset($item['offer_id'])) {
                    $offer = Product::find($item['offer_id']);
                    if (!$offer || (!$offer->is_offer || !$offer->publish)) {
                        return $this->errorResponse('Offer is unavailable or inactive', 400);
                    }

                    $unitPrice = $offer->offer_price;
                    $itemPrice = $unitPrice * $item['quantity'];
                    $totalPrice += $itemPrice;

                    $orderItems[] = [
                        'is_offer' => 1,
                        'offer_id' => $item['offer_id'],
                        'product_item_id' => null,
                        'quantity' => $item['quantity'],
                        'unitPrice' => $unitPrice,
                        'total_price' => $itemPrice,
                        'is_discount' => 0,
                        'unit_discount_price' => 0,
                        'unit_price_after_discount' => $unitPrice,
                        'customisations' => [],
                    ];
                } else {
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
                        'is_offer' => 0,
                        'offer_id' => null,
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
                'customer_location' => $address->location,
                'customer_latitude' => $address->latitude,
                'customer_longitude' => $address->longitude,
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
                    'offer_id' => $item['offer_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unitPrice'],
                    'is_discount' => $item['is_discount'],
                    'unit_discount_price' => $item['unit_discount_price'],
                    'unit_price_after_discount' => $item['unit_price_after_discount'],
                    'total_price' => $item['total_price'],
                    'is_offer' => $item['is_offer'],
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

            $formattedOrder = Order::with([
                'orderItems.productItem.product.images',
                'orderItems.customisations.customisation',
                'orderItems.customisations.customProduct',
                'orderItems.offer.images', // علاقات العرض
            ])->find($order->id);

            if (!$formattedOrder) {
                return $this->errorResponse('Order not found', 404);
            }

            $output = $orderFormattingService->formatOrder($formattedOrder);

            return $this->successResponse([
                'order' => $output,
                'wallet_balance' => $customer->wallet,

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


    public function index(Request $request, OrderService $orderFormattingService)
    {
        $perPage = $request->get('per_page', 10);
        $orders = Order::where('customer_id', auth()->id())
            ->with([
                'orderItems.productItem.product.images',
                'orderItems.customisations.customisation',
                'orderItems.customisations.customProduct',
                'orderItems.offer.images', // لجلب صور العرض
            ])
            ->paginate($perPage);

        $formattedOrders = $orderFormattingService->formatOrders($orders);

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





    public function submitReturn(Request $request, ReturnService $returnService)
    {
        return DB::transaction(function () use ($request, $returnService) {
            $request->validate([
                'order_id' => 'required|exists:orders,id',
                'items' => 'required|array',
                'items.*.order_item_id' => 'required|exists:order_items,id',
                'items.*.quantity' => 'required|integer|min:1',
                'reason' => 'nullable|string',
                'images' => 'nullable|array',
                'images.*' => 'file|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
                'payment_method' => 'required|in:cash_on_delivery,card_payment,wallet,e_wallet,bank',
            ]);

            $order = Order::find($request->order_id);

            if (!$order->is_returnable) {
                return response()->json(['message' => 'This order is not returnable.'], 400);
            }

            $customer = auth()->user();
            $vendor = Vendor::find($order->vendor_id);
            $address = Address::where('customer_id', $customer->id)
                ->where('is_default', true)
                ->first();

            if (!$address) {
                return $this->errorResponse('No default address found for the customer', 400);
            }
            $deliveryDetails = $address->calculateDeliveryDistanceAndTime($vendor);
            $returnItems = [];
            $totalReturnPrice = 0;
            $deliveryFee = $this->calculateDeliveryFee($deliveryDetails['distance_km']);
            foreach ($request->items as $item) {
                $orderItem = $order->orderItems()->find($item['order_item_id']);

                if ($item['quantity'] > $orderItem->quantity) {
                    return response()->json(['message' => 'Return quantity exceeds ordered quantity.'], 400);
                }

                $itemPrice = $orderItem->unit_price_after_discount * $item['quantity'];
                $totalCustomisationPrice = 0;
                $customisations = $orderItem->customisations;

                foreach ($customisations as $customisation) {
                    $totalCustomisationPrice += $customisation->total_price;
                }

                $itemPrice += $totalCustomisationPrice;

                $returnItems[] = [
                    'order_item_id' => $orderItem->id,
                    'quantity' => $item['quantity'],
                    'unit_price' => $orderItem->unit_price_after_discount,
                    'total_price' => $itemPrice,
                    'total_customisation_price' => $totalCustomisationPrice,
                ];

                $totalReturnPrice += $itemPrice;
            }

            if ($request->payment_method === 'wallet') {
                if ($customer->wallet < $deliveryFee) {

                    return $this->errorResponse('Insufficient wallet balance for the order', 400);
                }

                // خصم من المحفظة
                $customer->wallet -= $deliveryFee;
                $customer->save();
            }

            $return = Returns::create([
                'order_id' => $order->id,
                'customer_id' => $customer->id,
                'delivery_fee' => $deliveryFee,
                'distance' => $deliveryDetails['distance_km'],
                'status' => 'pending',
                'reason' => $request->reason,
                'vendor_id' => $vendor->id,
                'customer_location' => $address->location,
                'customer_latitude' => $address->latitude,
                'customer_longitude' => $address->longitude,
                'return_price' => $totalReturnPrice,
                'payment_method' => $request->payment_method,
                'payment_status' => $request->payment_method === 'wallet' ? 'paid' : 'pending',
            ]);

            foreach ($returnItems as $item) {
                ReturnItem::create(array_merge($item, ['return_id' => $return->id]));
            }

            $imageUrls = [];
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $path = $image->store('uploads/returns', 'public');
                    $imageUrls[] = $path;
                }
            }

            foreach ($imageUrls as $imageUrl) {
                ReturnImage::create([
                    'returns_id' => $return->id,
                    'image_url' => $imageUrl,
                ]);
            }

            $formattedReturns = Returns::where('customer_id', auth()->id())
                ->with([
                    'returnItems.orderItem.productItem.product.images',
                    'returnItems.orderItem.customisations.customisation',
                    'returnItems.orderItem.customisations.customProduct',
                    'returnItems.orderItem.offer.images',
                    'returnImages'
                ])->find($return->id);

            if (!$formattedReturns) {
                return $this->errorResponse('returns not found', 404);
            }
            $output = $returnService->formatReturn($formattedReturns);
            return response()->json([
                'returns' => $output,
                'total_return_price' => $totalReturnPrice,


            ], 201);
        });
    }


    public function getReturns(Request $request, ReturnService $returnService)
    {
        $perPage = $request->get('per_page', 10);
        $returns = Returns::where('customer_id', auth()->id())
            ->with([
                'returnItems.orderItem.productItem.product.images',
                'returnItems.orderItem.customisations.customisation',
                'returnItems.orderItem.customisations.customProduct',
                'returnItems.orderItem.offer.images',
                'returnImages'
            ])->paginate($perPage);

        $formattedReturns = $returnService->formatReturns($returns);

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





    // direct order

    public function addDirectOrder(Request $request)
    {
        // التحقق من البيانات المرسلة
        $request->validate([
            'vendor_lat' => 'nullable|numeric',
            'vendor_long' => 'nullable|numeric',
            'vendor_id' => 'nullable|exists:vendors,id',
            'vendor_name' => 'nullable|string|max:255',
            'payment_method' => 'required|in:cash_on_delivery,card_payment,wallet,e_wallet,bank',
            'items' => 'required|array',
            'items.*.name' => 'required|string|max:255',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        // المتغيرات الخاصة بالفيندور
        $vendor_lat = null;
        $vendor_long = null;
        $vendor_name = null;
        $deliveryDetails = null;

        // الحصول على الكاستمر الحالي من عملية الأوث
        $customer = auth()->user();
        $address = Address::where('customer_id', $customer->id)
            ->where('is_default', true)
            ->first();

        if (!$address) {
            return $this->errorResponse('No default address found for the customer', 400);
        }

        // بدء المعاملة
        DB::beginTransaction();

        try {
            if ($request->vendor_id) {
                // البحث عن الفيندور باستخدام id
                $vendor = Vendor::find($request->vendor_id);

                // إذا لم يوجد الفيندور بناءً على id المرسل
                if (!$vendor) {
                    return $this->errorResponse('Vendor not found', 404);
                }

                // إذا كان الفيندور موجودًا، نقوم بإضافة إحداثياته
                $vendor_lat = $vendor->latitude;
                $vendor_long = $vendor->longitude;
                $vendor_name = $vendor->name;
                $deliveryDetails = $address->calculateDeliveryDistanceAndTime($vendor);
            } else {
                // إذا لم يتم إرسال vendor_id، نأخذ الإحداثيات من المدخلات (إذا كانت موجودة)
                $vendor_lat = $request->vendor_lat;
                $vendor_long = $request->vendor_long;
                $vendor_name = $request->vendor_name;
                $deliveryDetails = $address->calculateDeliveryDistanceAndTime(null, $vendor_lat, $vendor_long);
            }

            // حساب رسوم التوصيل
            $deliveryFee = $this->calculateDeliveryFee($deliveryDetails['distance_km']);

            // تحقق من رصيد المحفظة إذا كان الدفع من المحفظة
            if ($request->payment_method === 'wallet') {
                if ($customer->wallet < $deliveryFee) {
                    return $this->errorResponse('Insufficient wallet balance for the order', 400);
                }

                // خصم من المحفظة
                $customer->wallet -= $deliveryFee;
                $customer->save();
            }

            // إعداد بيانات الطلب
            $orderData = [
                'customer_lat' => $address->latitude,
                'customer_long' => $address->longitude,
                'vendor_lat' => $vendor_lat,
                'vendor_long' => $vendor_long,
                'distance' => $deliveryDetails['distance_km'],
                'delivery_fee' => $deliveryFee,
                'payment_method' => $request->payment_method,
                'payment_status' => $request->payment_method === 'wallet' ? 'paid' : 'pending',
                'customer_id' => $customer->id,
                'is_vendor' => $request->vendor_id ? true : false,
                'vendor_id' => $request->vendor_id ?? null,
                'vendor_name' => $vendor_name,
            ];

            // إنشاء الطلب
            $directOrder = new DirectOrder($orderData);
            $directOrder->save();

            // تخزين العناصر المرتبطة بالطلب
            foreach ($request->items as $item) {
                $directOrder->items()->create([
                    'name' => $item['name'],
                    'quantity' => $item['quantity'],
                ]);
            }

            // تنفيذ المعاملة
            DB::commit();

            // إرجاع استجابة بنجاح العملية
            return $this->successResponse([
                'direct_order' => $directOrder,

            ], 'Direct order added successfully!', 201, );

        } catch (\Exception $e) {
            // في حالة حدوث أي خطأ، نقوم بالتراجع عن كل العمليات
            DB::rollBack();

            return $this->errorResponse('Something went wrong, please try again.', 500);
        }
    }

    public function getUserDirectOrders()
    {
        // الحصول على المستخدم الحالي
        $customer = auth()->user();

        // جلب الطلبات المرتبطة بالمستخدم
        $directOrders = DirectOrder::where('customer_id', $customer->id)->with('items')->get();

        // استخدام Resource لتنسيق البيانات
        return $this->successResponse([
            'orders' => DirectOrderResource::collection($directOrders),
        ]);
    }
}
