<?php

namespace App\Http\Controllers\Api\Users;

use App\Http\Controllers\Controller;
use App\Models\DeliveryAgent;
use App\Models\DeliveryAgentOrder;
use App\Models\Order;
use App\Models\OrderStatusLog;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class OrdersController extends Controller
{
    use ApiResponse;
    public function index(Request $request)
    {
        // تحديد عدد العناصر في الصفحة (افتراضيًا 10)
        $perPage = $request->get('per_page', 10);

        // جلب جميع الطلبات مع بياناتهم المترابطة
        $orders = Order::with([
            'orderItems.productItem.product.images',
            'orderItems.customisations.customisation',
            'orderItems.customisations.customProduct',
            'vendor',
            'address',
            'deliveryAgent',
            'customer' // إذا كان يوجد وكيل توصيل
        ])
            ->paginate($perPage);

        // تنسيق البيانات لعرضها بشكل مناسب
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
        ], 'Orders retrieved successfully');
    }


    public function assignDeliveryAgent(Request $request, $orderId)
    {
        // التحقق من وجود السائق
        $validated = $request->validate([
            'delivery_agent_id' => 'required|exists:delivery_agents,id',
        ]);

        $order = Order::findOrFail($orderId);
        $deliveryAgent = DeliveryAgent::find($request->delivery_agent_id);


        if ($order->status != 'pending') {
            return $this->errorResponse('لا يمكن تعيين السائق لأن حالة الطلب ليست "معلق".', 400);
        }


        $deliveryAgentOrder = DeliveryAgentOrder::create([
            'order_id' => $order->id,
            'delivery_agent_id' => $deliveryAgent->id,
            'is_accepted' => false, // السائق لم يقبل الطلب بعد
        ]);


        $order->status = 'order_assigned';
        $order->save();


        OrderStatusLog::create([
            'order_id' => $order->id,
            'status' => 'order_assigned',
            'changed_by' => auth()->id(),
        ]);

        return $this->successResponse([
            'order' => $order,
            'message' => 'تم تعيين السائق بنجاح.'
        ]);
    }

    // API لتغيير حالة الطلب
    public function changeOrderStatus(Request $request, $orderId)
    {
        // التحقق من حالة الطلب المرسلة
        $validated = $request->validate([
            'status' => 'required|in:pending,order_assigned,on_the_way,picked_up,delivered,completed,failed,refunded,cancelled',
        ]);

        $order = Order::findOrFail($orderId);

        // تغيير حالة الطلب
        $order->status = $request->status;
        $order->save();

        // إضافة سجل لحالة الطلب
        OrderStatusLog::create([
            'order_id' => $order->id,
            'status' => $request->status,
            'changed_by' => auth()->id(),
        ]);

        return $this->successResponse([
            'order' => $order,
            'message' => 'تم تحديث حالة الطلب بنجاح.'
        ]);
    }
}
