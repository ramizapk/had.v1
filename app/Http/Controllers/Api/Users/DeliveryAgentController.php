<?php

namespace App\Http\Controllers\Api\Users;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\DeliveryAgent\DeliveryAgentResource;
use App\Models\DeliveryAgent;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DeliveryAgentController extends Controller
{
    use ApiResponse;

    /**
     * إنشاء حساب لعامل التوصيل
     */
    public function createAgent(Request $request)
    {
        // التحقق من صحة البيانات المدخلة
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'phone' => 'required|string|unique:delivery_agents,phone',
            'password' => 'required|string|min:6',
            'address' => 'nullable|string|max:255',
            'region' => 'nullable|string|max:255',
            'balance' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors(), 422);
        }

        // إنشاء رقم عامل فريد
        $agent_no = $this->generateUniqueAgentNo();

        // إنشاء عامل التوصيل
        $deliveryAgent = DeliveryAgent::create([
            'name' => $request->name,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'address' => $request->address,
            'region' => $request->region,
            'agent_no' => $agent_no,
            'is_active' => true,
            'balance' => $request->balance ?? 0.00,
        ]);

        return $this->successResponse(new DeliveryAgentResource($deliveryAgent), 'تم إنشاء حساب عامل التوصيل بنجاح');
    }


    private function generateUniqueAgentNo()
    {
        // توليد رقم عشوائي مكون من 6 أرقام فقط
        do {
            $agentNo = rand(100000, 999999);  // رقم مكون من 6 أرقام فقط
        } while (DeliveryAgent::where('agent_no', $agentNo)->exists());  // تحقق من الفريدة

        return $agentNo;
    }
}
