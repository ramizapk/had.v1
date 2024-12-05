<?php

namespace App\Http\Controllers\Api\DeliveryAgents;

use App\Http\Controllers\Controller;
use App\Models\DeliveryAgentOrder;
use App\Models\Order;
use App\Models\OrderStatusLog;
use Illuminate\Http\Request;
use App\Traits\ApiResponse;
class OrdersController extends Controller
{
    use ApiResponse;
    public function handleOrderAcceptance(Request $request, $orderId)
    {
        // $validatedData = $request->validate([
        //     'accept' => 'required|boolean', // يجب أن تكون القيمة موجودة ومنطقية (true/false)
        // ]);
        // التحقق من وجود الطلب
        $order = Order::findOrFail($orderId);

        // التحقق من وجود السائق
        $deliveryAgentOrder = DeliveryAgentOrder::where('order_id', $orderId)
            ->where('delivery_agent_id', auth()->id())
            ->first();

        if (!$deliveryAgentOrder) {
            return $this->errorResponse('الطلب غير مخصص لهذا السائق.', 404);
        }

        if ($deliveryAgentOrder->is_accepted || $deliveryAgentOrder->is_rejected) {
            return $this->errorResponse('لقد تم التعامل مع الطلب بالفعل.', 400);
        }

        // قبول أو رفض الطلب بناءً على المعاملات
        if ($request->accept) {
            // قبول الطلب
            $deliveryAgentOrder->acceptOrder();
            $order->status = 'on_the_way';  // تغيير الحالة إلى "في الطريق"
            $order->delivery_agent_id = auth()->id();
            DeliveryAgentOrder::where('order_id', $orderId)
                ->where('delivery_agent_id', '!=', auth()->id()) // استثناء السائق الحالي
                ->delete();
            $statusMessage = 'تم قبول الطلب من قبل السائق وتغيير حالته إلى "في الطريق".';
        } else {
            // رفض الطلب
            $deliveryAgentOrder->rejectOrder();
            $statusMessage = 'تم رفض الطلب من قبل السائق وتغيير حالته إلى "معلق".';
        }

        // حفظ التغييرات في الطلب
        $order->save();

        return $this->successResponse(
            $order,
            $statusMessage
        );
    }

    // API لتغيير حالة الطلب إلى "في الطريق" أو "تم استلامه" أو "تم التوصيل"
    public function updateOrderStatus(Request $request, $orderId)
    {
        // التحقق من وجود الطلب
        $order = Order::findOrFail($orderId);

        // التحقق من الحالة الجديدة وإجراء التحديث بناءً عليها
        $newStatus = $request->status;

        // التحقق من صحة الحالة المدخلة
        $validStatuses = ['on_the_way', 'picked_up', 'delivered'];
        if (!in_array($newStatus, $validStatuses)) {
            return $this->errorResponse('الحالة غير صحيحة.', 400);
        }

        // التحقق من الترتيب المنطقي للحالات (يجب أن تكون الحالة التالية متسقة مع السابقة)
        if ($newStatus == 'picked_up' && $order->status != 'on_the_way') {
            return $this->errorResponse('لا يمكن تغيير الحالة إلى "تم استلامه" لأن الطلب ليس في الطريق.', 400);
        }

        if ($newStatus == 'delivered' && $order->status != 'picked_up') {
            return $this->errorResponse('لا يمكن تغيير الحالة إلى "تم التوصيل" لأن الطلب لم يتم استلامه بعد.', 400);
        }

        // تحديث حالة الطلب
        $order->status = $newStatus;
        $order->save();

        return $this->successResponse(
            $order,
            "تم تحديث حالة الطلب إلى \"$newStatus\"."
        );
    }


    public function getNewOrdersForAgent(Request $request)
    {
        // جلب الطلبات التي تخص السائق الحالي في حالة "معلق" أو "في الطريق"
        $orders = Order::with(relations: [
            'orderItems.productItem.product.images',
            'orderItems.customisations.customisation',
            'orderItems.customisations.customProduct',
            'vendor',
            'address',
            'deliveryAgent',
            'customer',
        ])
            ->whereHas('deliveryAgentOrders', function ($query) {
                // تحقق من أن السائق الحالي هو المعين للطلب
                $query->where('delivery_agent_id', auth()->id());
            })
            ->whereIn('status', ['order_assigned'])
            ->orderBy('created_at', 'desc')  // ترتيب حسب تاريخ الإنشاء (أحدث أولاً)
            ->paginate(10);  // تحديد عدد العناصر في الصفحة، يمكن تعديله بناءً على الحاجة
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
                'customer' => [
                    'id' => $order->customer->id,
                    'name' => $order->customer->name,
                    'phone_number' => $order->customer->phone_number,
                    'avatar' => $order->customer->avatar ? url($order->customer->avatar) : null,
                    'wallet' => $order->customer->wallet,
                    'is_active' => $order->customer->is_active,
                    'is_suspended' => $order->customer->is_suspended,
                ],
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
        return $this->successResponse([
            'data' => $formattedOrders,
            'pagination' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
            ],
        ], 'الطلبات الحديثة الخاصة بالسائق تم استرجاعها بنجاح');
    }


    public function getCompletedOrdersForAgent(Request $request)
    {
        // جلب الطلبات التي تخص السائق الحالي في حالة "تم استلامه" أو "تم التوصيل"
        $orders = Order::with([
            'orderItems.productItem.product.images',
            'orderItems.customisations.customisation',
            'orderItems.customisations.customProduct',
            'vendor',
            'address',
            'deliveryAgent',
            'customer',
        ])
            ->whereHas('deliveryAgentOrders', function ($query) {
                // تحقق من أن السائق الحالي هو المعين للطلب
                $query->where('delivery_agent_id', auth()->id());
            })
            ->whereIn('status', ['completed', 'delivered', 'refunded',])
            ->orderBy('updated_at', 'desc')  // ترتيب حسب تاريخ التحديث (أحدث أولاً)
            ->paginate(10);  // تحديد عدد العناصر في الصفحة، يمكن تعديله بناءً على الحاجة

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
                'customer' => [
                    'id' => $order->customer->id,
                    'name' => $order->customer->name,
                    'phone_number' => $order->customer->phone_number,
                    'avatar' => $order->customer->avatar ? url($order->customer->avatar) : null,
                    'wallet' => $order->customer->wallet,
                    'is_active' => $order->customer->is_active,
                    'is_suspended' => $order->customer->is_suspended,
                ],
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
        ], 'الطلبات المنفذة الخاصة بالسائق تم استرجاعها بنجاح');
    }


    public function getUnderProcessingOrdersForAgent(Request $request)
    {
        // جلب الطلبات التي تخص السائق الحالي في حالة "معلق" أو "في الطريق"
        $orders = Order::with([
            'orderItems.productItem.product.images',
            'orderItems.customisations.customisation',
            'orderItems.customisations.customProduct',
            'vendor',
            'address',
            'deliveryAgent',
            'customer',
        ])
            ->whereHas('deliveryAgentOrders', function ($query) {
                // تحقق من أن السائق الحالي هو المعين للطلب
                $query->where('delivery_agent_id', auth()->id());
            })
            ->whereIn('status', ['on_the_way', 'picked_up'])
            ->orderBy('created_at', 'desc')  // ترتيب حسب تاريخ الإنشاء (أحدث أولاً)
            ->paginate(10);  // تحديد عدد العناصر في الصفحة، يمكن تعديله بناءً على الحاجة
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
                'customer' => [
                    'id' => $order->customer->id,
                    'name' => $order->customer->name,
                    'phone_number' => $order->customer->phone_number,
                    'avatar' => $order->customer->avatar ? url($order->customer->avatar) : null,
                    'wallet' => $order->customer->wallet,
                    'is_active' => $order->customer->is_active,
                    'is_suspended' => $order->customer->is_suspended,
                ],
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
        return $this->successResponse([
            'data' => $formattedOrders,
            'pagination' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
            ],
        ], 'الطلبات تحت المعالجة الخاصة بالسائق تم استرجاعها بنجاح');
    }

}
