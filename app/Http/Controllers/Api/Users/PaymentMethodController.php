<?php

namespace App\Http\Controllers\Api\Users;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\PaymentMethodResource;
use App\Models\PaymentMethod;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
class PaymentMethodController extends Controller
{
    use ApiResponse;

    /**
     * عرض جميع وسائل الدفع.
     */
    public function index()
    {
        $methods = PaymentMethod::all();
        return $this->successResponse(PaymentMethodResource::collection($methods), 'تم جلب وسائل الدفع بنجاح');
    }

    /**
     * إضافة وسيلة دفع جديدة.
     */
    public function store(Request $request)
    {
        $request->validate([
            'method_name' => 'required|string|max:255',
            'account_name' => 'nullable|string|max:255',
            'account_number' => 'nullable|string|max:255',
            'company_logo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $logoPath = null;
        if ($request->hasFile('company_logo')) {
            $logoPath = $request->file('company_logo')->store('uploads/payment_methods', 'public');
        }

        $method = PaymentMethod::create([
            'method_name' => $request->method_name,
            'account_name' => $request->account_name,
            'account_number' => $request->account_number,
            'company_logo' => $logoPath,
        ]);

        return $this->successResponse(new PaymentMethodResource($method), 'تمت إضافة وسيلة الدفع بنجاح', 201);
    }

    /**
     * تحديث وسيلة دفع.
     */
    public function update(Request $request, PaymentMethod $paymentMethod)
    {
        $request->validate([
            'method_name' => 'sometimes|required|string|max:255',
            'account_name' => 'nullable|string|max:255',
            'account_number' => 'nullable|string|max:255',
            'company_logo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        if ($request->hasFile('company_logo')) {
            if ($paymentMethod->company_logo && Storage::exists('public/' . $paymentMethod->company_logo)) {
                Storage::delete('public/' . $paymentMethod->company_logo);
            }
            $paymentMethod->company_logo = $request->file('company_logo')->store('uploads/payment_methods', 'public');
        }

        $paymentMethod->update($request->only('method_name', 'account_name', 'account_number', 'company_logo'));

        return $this->successResponse(new PaymentMethodResource($paymentMethod), 'تم تحديث وسيلة الدفع بنجاح');
    }

    /**
     * حذف وسيلة دفع.
     */
    public function destroy(PaymentMethod $paymentMethod)
    {
        if ($paymentMethod->company_logo && Storage::exists('public/' . $paymentMethod->company_logo)) {
            Storage::delete('public/' . $paymentMethod->company_logo);
        }

        $paymentMethod->delete();

        return $this->successResponse(null, 'تم حذف وسيلة الدفع بنجاح');
    }
}
