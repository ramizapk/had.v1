<?php


namespace App\Services\CustomerService\CustomerAuthService;

use App\Http\Resources\V1\App\CustomerResource;
use App\Models\Customer;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;


class CustomerLoginService
{
    use ApiResponse;

    protected $model;

    /**
     * Create a new class instance.
     */
    public function __construct(Customer $customer)
    {
        $this->model = $customer;
    }

    // get user by phone number
    protected function getUser($phoneNumber)
    {
        $user = $this->model->where('phone_number', $phoneNumber)->first();

        if (!$user) {
            return $this->errorResponse('المستخدم غير موجود.', 404);
        }

        if (!$user->is_active) {
            return $this->errorResponse('الحساب غير مفعل. يرجى استكمال عملية التحقق.', 409);
        }

        return $user;
    }

    // validate user and password
    protected function validateUser($user, $request)
    {
        if (!$user || !Hash::check($request->password, $user->password)) {
            return $this->errorResponse('The provided credentials are incorrect.', 422);
        }
        return $user;
    }

    // login customer with phone number and password
    public function login($request)
    {
        // محاولة الحصول على المستخدم من قاعدة البيانات باستخدام رقم الهاتف
        $user = $this->getUser($request->phone_number);

        if ($user instanceof \Illuminate\Http\JsonResponse) {
            return $user;
        }
        // التحقق من كلمة المرور المدخلة
        $user = $this->validateUser($user, $request);

        // التحقق إذا كان الحساب محظور
        if ($user->is_suspended) {
            return $this->errorResponse('هذا الحساب محظور حالياً، يرجى التواصل مع الادارة', 422);

        }

        // إنشاء التوكن (رمز الوصول)
        $token = $user->createToken('auth_token')->plainTextToken;

        // تحميل العناوين إذا كانت موجودة
        $user->load('addresses');

        // إرجاع الاستجابة باستخدام التريت
        return $this->successResponse([
            'customer' => new CustomerResource($user),
            'access_token' => $token
        ], 'Login successful.', 200);
    }
}
