<?php

namespace App\Http\Controllers\Api\Users;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\Admin\WalletTransactionResource;
use App\Models\Customer;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
class WalletController extends Controller
{
    use ApiResponse;  // استخدام التريت

    public function modifyWallet(Request $request)
    {
        // تحقق من أن المستخدم لديه توكن صالح
        $user = Auth::user();  // هذا سيحصل على المستخدم بناءً على التوكن المرسل في الطلب

        // تحقق من وجود المستخدم
        if (!$user) {
            return $this->errorResponse('Unauthorized', 401);
        }

        // تحقق من صحة المدخلات
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'amount' => 'required|numeric',
            'transaction_type' => 'required|in:add,update',
            'description' => 'nullable|string|max:255',
        ]);

        // ابحث عن العميل باستخدام ID
        $customer = Customer::find($request->customer_id);

        // تحقق من وجود العميل
        if (!$customer) {
            return $this->errorResponse('Customer not found', 404);
        }

        // تعديل المحفظة بناءً على نوع العملية
        if ($request->transaction_type === 'add') {
            $customer->wallet += $request->amount;  // إضافة المبلغ
        } elseif ($request->transaction_type === 'update') {
            $customer->wallet = $request->amount;  // تحديث المحفظة بالمبلغ الجديد
        }
        $customer->save();  // حفظ التغييرات في قاعدة البيانات

        // تسجيل العملية في جدول المحفظة
        $transaction = WalletTransaction::create([
            'customer_id' => $customer->id,
            'created_by' => $user->id,  // سيتم استخدام ID المستخدم الذي قام بالعملية
            'amount' => $request->amount,
            'transaction_type' => $request->transaction_type,
            'description' => $request->description,
        ]);
        $transaction->load(['customer', 'user']);
        return $this->successResponse([
            'message' => 'Wallet updated successfully',
            'transaction' => new WalletTransactionResource($transaction),
        ]);
    }

    /**
     * عرض كل العمليات المتعلقة بمحفظة عميل معين.
     */
    public function getCustomerTransactions($customerId)
    {
        $transactions = WalletTransaction::where('customer_id', $customerId)->with(['customer', 'user'])->get();

        if ($transactions->isEmpty()) {
            return $this->errorResponse('No transactions found', 404);
        }

        return $this->successResponse(WalletTransactionResource::collection($transactions));
    }


    public function getAllTransactions()
    {
        $transactions = WalletTransaction::with('customer', 'user')->get();

        if ($transactions->isEmpty()) {
            return $this->errorResponse('No transactions found', 404);
        }

        return $this->successResponse(WalletTransactionResource::collection($transactions));
    }
}
