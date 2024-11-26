<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\DeliveryAgent\DeliveryAgentResource;
use App\Models\DeliveryAgent;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class DeliveryAuthController extends Controller
{
    use ApiResponse;

    /**
     * تسجيل الدخول لعمال التوصيل
     */
    public function login(Request $request)
    {
        // التحقق من صحة البيانات المدخلة
        $validator = Validator::make($request->all(), [
            'identifier' => 'required', // يمكن أن يكون رقم الهاتف أو رقم العميل
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors(), 422);
        }

        // البحث عن العامل باستخدام رقم الهاتف أو رقم العميل
        $agent = DeliveryAgent::where('phone', $request->identifier)
            ->orWhere('agent_no', $request->identifier)
            ->first();

        if (!$agent) {
            return $this->errorResponse('العامل غير موجود', 404);
        }

        // التحقق من كلمة المرور
        if (!Hash::check($request->password, $agent->password)) {
            return $this->errorResponse('كلمة المرور غير صحيحة', 401);
        }

        // التحقق من حالة العامل
        if (!$agent->is_active) {
            return $this->errorResponse('حساب العامل غير مفعل', 403);
        }

        // إنشاء Token للعامل
        $token = $agent->createToken('DeliveryAgentToken')->plainTextToken;

        return $this->successResponse([
            'agent' => new DeliveryAgentResource($agent),
            'token' => $token,
        ], 'تم تسجيل الدخول بنجاح');
    }

    /**
     * تسجيل الخروج لعمال التوصيل
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return $this->successResponse(null, 'تم تسجيل الخروج بنجاح');
    }
}
