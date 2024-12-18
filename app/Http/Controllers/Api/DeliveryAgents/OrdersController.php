<?php

namespace App\Http\Controllers\Api\DeliveryAgents;

use App\Http\Controllers\Controller;
use App\Models\DeliveryAgentOrder;
use App\Models\DeliveryAgentReturn;
use App\Models\Order;
use App\Models\OrderStatusLog;
use App\Models\Returns;
use App\Services\ReturnService;
use Illuminate\Http\Request;
use App\Traits\ApiResponse;
use App\Services\OrderService;
class OrdersController extends Controller
{
    use ApiResponse;
    public function handleOrderAcceptance(Request $request, $orderId, OrderService $orderFormattingService)
    {
        // $validatedData = $request->validate([
        //     'accept' => 'required|boolean', // يجب أن تكون القيمة موجودة ومنطقية (true/false)
        // ]);
        // التحقق من وجود الطلب
        $order = Order::with([
            'orderItems.productItem.product.images',
            'orderItems.customisations.customisation',
            'orderItems.customisations.customProduct',
            'vendor',
            'deliveryAgent',
            'customer',
        ])->findOrFail($orderId);

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
        $formattedOrders = $orderFormattingService->formatOrder($order);
        return $this->successResponse(
            $formattedOrders,
            $statusMessage
        );
    }

    // API لتغيير حالة الطلب إلى "في الطريق" أو "تم استلامه" أو "تم التوصيل"
    public function updateOrderStatus(Request $request, $orderId, OrderService $orderFormattingService)
    {
        // التحقق من وجود الطلب
        $order = Order::with([
            'orderItems.productItem.product.images',
            'orderItems.customisations.customisation',
            'orderItems.customisations.customProduct',
            'vendor',

            'deliveryAgent',
            'customer',
        ])->whereHas('deliveryAgentOrders', function ($query) {
            // تحقق من أن السائق الحالي هو المعين للطلب
            $query->where('delivery_agent_id', auth()->id());
        })->findOrFail($orderId);

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
        if ($newStatus == 'delivered' && $order->status != 'delivered') {
            return $this->errorResponse('لا يمكن تغيير الحالة إلى "تم التوصيل" لأن الطلب لم يتم استلامه بعد.', 400);
        }

        // تحديث حالة الطلب
        $order->status = $newStatus;
        $order->save();
        $formattedOrders = $orderFormattingService->formatOrder($order);
        return $this->successResponse(
            $formattedOrders,
            "تم تحديث حالة الطلب إلى \"$newStatus\"."
        );
    }


    public function getNewOrdersForAgent(Request $request, OrderService $orderFormattingService)
    {
        // جلب الطلبات التي تخص السائق الحالي في حالة "معلق" أو "في الطريق"
        $orders = Order::with(relations: [
            'orderItems.productItem.product.images',
            'orderItems.customisations.customisation',
            'orderItems.customisations.customProduct',
            'vendor',

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

        $formattedOrders = $orderFormattingService->formatOrders($orders);
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


    public function getCompletedOrdersForAgent(Request $request, OrderService $orderFormattingService)
    {
        // جلب الطلبات التي تخص السائق الحالي في حالة "تم استلامه" أو "تم التوصيل"
        $orders = Order::with([
            'orderItems.productItem.product.images',
            'orderItems.customisations.customisation',
            'orderItems.customisations.customProduct',
            'vendor',

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

        $formattedOrders = $orderFormattingService->formatOrders($orders);

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


    public function getUnderProcessingOrdersForAgent(Request $request, OrderService $orderFormattingService)
    {
        // جلب الطلبات التي تخص السائق الحالي في حالة "معلق" أو "في الطريق"
        $orders = Order::with([
            'orderItems.productItem.product.images',
            'orderItems.customisations.customisation',
            'orderItems.customisations.customProduct',
            'vendor',

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
        $formattedOrders = $orderFormattingService->formatOrders($orders);

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


    // returns here 

    public function handleReturnAcceptance(Request $request, $returnId, ReturnService $returnsFormattingService)
    {

        // التحقق من وجود الطلب
        $returns = Returns::with([
            'returnItems.orderItem.productItem.product.images',
            'returnItems.orderItem.customisations.customisation',
            'returnItems.orderItem.customisations.customProduct',
            'returnItems.orderItem.offer.images',
            'returnImages'
        ])->findOrFail($returnId);

        // التحقق من وجود السائق
        $deliveryAgentReturn = DeliveryAgentReturn::where('returns_id', $returnId)
            ->where('delivery_agent_id', auth()->id())
            ->first();

        if (!$deliveryAgentReturn) {
            return $this->errorResponse('الطلب غير مخصص لهذا السائق.', 404);
        }

        if ($deliveryAgentReturn->is_accepted || $deliveryAgentReturn->is_rejected) {
            return $this->errorResponse('لقد تم التعامل مع الطلب بالفعل.', 400);
        }

        // قبول أو رفض الطلب بناءً على المعاملات
        if ($request->accept) {
            // قبول الطلب
            $deliveryAgentReturn->acceptOrder();
            $returns->status = 'accepted';  // تغيير الحالة إلى "في الطريق"
            $returns->delivery_agent_id = auth()->id();
            DeliveryAgentReturn::where('returns_id', $returnId)
                ->where('delivery_agent_id', '!=', auth()->id()) // استثناء السائق الحالي
                ->delete();
            $statusMessage = 'تم قبول الطلب من قبل السائق وتغيير حالته إلى "في الطريق".';
        } else {
            // رفض الطلب
            $deliveryAgentReturn->rejectOrder();
            $statusMessage = 'تم رفض الطلب من قبل السائق وتغيير حالته إلى "معلق".';
        }

        // حفظ التغييرات في الطلب
        $returns->save();
        $formattedReturns = $returnsFormattingService->formatReturn($returns);
        return $this->successResponse(
            $formattedReturns,
            $statusMessage
        );
    }


    public function updateReturnStatus(Request $request, $returnId, ReturnService $returnsFormattingService)
    {
        // التحقق من وجود الطلب
        $returns = Returns::with([
            'returnItems.orderItem.productItem.product.images',
            'returnItems.orderItem.customisations.customisation',
            'returnItems.orderItem.customisations.customProduct',
            'returnItems.orderItem.offer.images',
            'returnImages'
        ])->whereHas('deliveryAgentReturns', function ($query) {
            // تحقق من أن السائق الحالي هو المعين للطلب
            $query->where('delivery_agent_id', auth()->id());
        })->findOrFail($returnId);

        // التحقق من الحالة الجديدة وإجراء التحديث بناءً عليها
        $newStatus = $request->status;

        // التحقق من صحة الحالة المدخلة
        $validStatuses = ['accepted', 'picked_up', 'returned_to_store', 'returned_product', 'refunded'];
        if (!in_array($newStatus, $validStatuses)) {
            return $this->errorResponse('الحالة غير صحيحة.', 400);
        }

        // التحقق من الترتيب المنطقي للحالات (يجب أن تكون الحالة التالية متسقة مع السابقة)
        if ($newStatus == 'picked_up' && $returns->status != 'accepted') {
            return $this->errorResponse('لا يمكن تغيير الحالة إلى "تم استلامه" لأن الطلب ليس في الطريق.', 400);
        }

        if ($newStatus == 'returned_to_store' && $returns->status != 'picked_up') {
            return $this->errorResponse('لا يمكن تغيير الحالة إلى "تم التوصيل" لأن الطلب لم يتم استلامه بعد.', 400);
        }

        if ($newStatus == 'returned_product' && $returns->status != 'returned_to_store') {
            return $this->errorResponse('لا يمكن تغيير الحالة إلى "تم التوصيل" لأن الطلب لم يتم استلامه بعد.', 400);
        }

        // تحديث حالة الطلب
        $returns->status = $newStatus;
        $returns->save();
        $formattedReturns = $returnsFormattingService->formatReturn($returns);
        return $this->successResponse(
            $formattedReturns,
            "تم تحديث حالة الطلب إلى \"$newStatus\"."
        );
    }

    public function getNewReturnsForAgent(Request $request, ReturnService $returnsFormattingService)
    {
        // جلب الطلبات التي تخص السائق الحالي في حالة "معلق" أو "في الطريق"
        $returns = Returns::with([
            'returnItems.orderItem.productItem.product.images',
            'returnItems.orderItem.customisations.customisation',
            'returnItems.orderItem.customisations.customProduct',
            'returnItems.orderItem.offer.images',
            'returnImages'
        ])
            ->whereHas('deliveryAgentReturns', function ($query) {
                // تحقق من أن السائق الحالي هو المعين للطلب
                $query->where('delivery_agent_id', auth()->id());
            })
            ->whereIn('status', ['approved'])
            ->orderBy('created_at', 'desc')  // ترتيب حسب تاريخ الإنشاء (أحدث أولاً)
            ->paginate(10);  // تحديد عدد العناصر في الصفحة، يمكن تعديله بناءً على الحاجة

        $formattedReturns = $returnsFormattingService->formatReturns($returns);
        return $this->successResponse([
            'data' => $formattedReturns,
            'pagination' => [
                'current_page' => $returns->currentPage(),
                'last_page' => $returns->lastPage(),
                'per_page' => $returns->perPage(),
                'total' => $returns->total(),
            ],
        ], 'الطلبات الحديثة الخاصة بالسائق تم استرجاعها بنجاح');
    }


    public function getCompletedReturnsForAgent(Request $request, ReturnService $returnsFormattingService)
    {
        // جلب الطلبات التي تخص السائق الحالي في حالة "تم استلامه" أو "تم التوصيل"
        $returns = Returns::with([
            'returnItems.orderItem.productItem.product.images',
            'returnItems.orderItem.customisations.customisation',
            'returnItems.orderItem.customisations.customProduct',
            'returnItems.orderItem.offer.images',
            'returnImages'
        ])
            ->whereHas('deliveryAgentReturns', function ($query) {
                // تحقق من أن السائق الحالي هو المعين للطلب
                $query->where('delivery_agent_id', auth()->id());
            })
            ->whereIn('status', ['refunded'])
            ->orderBy('updated_at', 'desc')  // ترتيب حسب تاريخ التحديث (أحدث أولاً)
            ->paginate(10);  // تحديد عدد العناصر في الصفحة، يمكن تعديله بناءً على الحاجة

        $formattedReturns = $returnsFormattingService->formatReturns($returns);

        // إضافة بيانات الباجينيشن إلى الاستجابة
        return $this->successResponse([
            'data' => $formattedReturns,
            'pagination' => [
                'current_page' => $returns->currentPage(),
                'last_page' => $returns->lastPage(),
                'per_page' => $returns->perPage(),
                'total' => $returns->total(),
            ],
        ], 'الطلبات المنفذة الخاصة بالسائق تم استرجاعها بنجاح');
    }


    public function getUnderProcessingReturnsForAgent(Request $request, ReturnService $returnsFormattingService)
    {
        // جلب الطلبات التي تخص السائق الحالي في حالة "معلق" أو "في الطريق"
        $returns = Returns::with([
            'returnItems.orderItem.productItem.product.images',
            'returnItems.orderItem.customisations.customisation',
            'returnItems.orderItem.customisations.customProduct',
            'returnItems.orderItem.offer.images',
            'returnImages'
        ])
            ->whereHas('deliveryAgentReturns', function ($query) {
                // تحقق من أن السائق الحالي هو المعين للطلب
                $query->where('delivery_agent_id', auth()->id());
            })
            ->whereIn('status', ['picked_up', 'returned_to_store', 'accepted', 'returned_product'])
            ->orderBy('created_at', 'desc')  // ترتيب حسب تاريخ الإنشاء (أحدث أولاً)
            ->paginate(10);  // تحديد عدد العناصر في الصفحة، يمكن تعديله بناءً على الحاجة
        $formattedReturns = $returnsFormattingService->formatReturns($returns);

        return $this->successResponse([
            'data' => $formattedReturns,
            'pagination' => [
                'current_page' => $returns->currentPage(),
                'last_page' => $returns->lastPage(),
                'per_page' => $returns->perPage(),
                'total' => $returns->total(),
            ],
        ], 'الطلبات تحت المعالجة الخاصة بالسائق تم استرجاعها بنجاح');
    }

}
