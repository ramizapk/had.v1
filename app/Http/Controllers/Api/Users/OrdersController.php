<?php

namespace App\Http\Controllers\Api\Users;

use App\Http\Controllers\Controller;
use App\Models\DeliveryAgent;
use App\Models\DeliveryAgentOrder;
use App\Models\DeliveryAgentReturn;
use App\Models\Order;
use App\Models\OrderStatusLog;
use App\Models\Returns;
use App\Models\ReturnStatusLog;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Services\OrderService;
use App\Services\ReturnService;
class OrdersController extends Controller
{
    use ApiResponse;
    public function index(Request $request, OrderService $orderFormattingService)
    {
        $perPage = $request->get('per_page', 10);

        // جلب الطلبات مع بياناتها المترابطة
        $orders = Order::with([
            'orderItems.productItem.product.images',
            'orderItems.customisations.customisation',
            'orderItems.customisations.customProduct',
            'vendor',

            'deliveryAgent',
            'customer',
        ])
            ->paginate($perPage);

        // تنسيق البيانات باستخدام الخدمة
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

    public function getReturns(Request $request, ReturnService $returnService)
    {
        $perPage = $request->get('per_page', 10);
        $returns = Returns::with([
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




    public function changeReturnStatus(Request $request, $returnId)
    {
        // التحقق من حالة الطلب المرسلة
        $validated = $request->validate([
            'status' => 'required|in:pending,order_assigned,on_the_way,picked_up,delivered,completed,failed,refunded,cancelled',
        ]);

        $return = Returns::findOrFail($returnId);

        // تغيير حالة الطلب
        $return->status = $request->status;
        $return->save();

        // إضافة سجل لحالة الطلب
        ReturnStatusLog::create([
            'returns_id' => $return->id,
            'status' => $request->status,
            'changed_by' => auth()->id(),
        ]);

        return $this->successResponse([
            'return' => $return,
            'message' => 'تم تحديث حالة المرجع بنجاح.'
        ]);
    }

    public function assignReturnsToDeliveryAgent(Request $request, $returnId)
    {
        // التحقق من وجود السائق
        $validated = $request->validate([
            'delivery_agent_id' => 'required|exists:delivery_agents,id',
        ]);

        $returns = Returns::findOrFail($returnId);
        $deliveryAgent = DeliveryAgent::find($request->delivery_agent_id);


        if ($returns->status != 'pending') {
            return $this->errorResponse('لا يمكن تعيين السائق لأن حالة المرجع ليست "معلق".', 400);
        }


        $deliveryAgentReturns = DeliveryAgentReturn::create([
            'returns_id' => $returns->id,
            'delivery_agent_id' => $deliveryAgent->id,
            'is_accepted' => false, // السائق لم يقبل الطلب بعد
        ]);


        $returns->status = 'approved';
        $returns->save();


        ReturnStatusLog::create([
            'returns_id' => $returns->id,
            'status' => 'approved',
            'changed_by' => auth()->id(),
        ]);

        return $this->successResponse([
            'return' => $returns,
            'message' => 'تم تعيين السائق بنجاح.'
        ]);
    }
}
