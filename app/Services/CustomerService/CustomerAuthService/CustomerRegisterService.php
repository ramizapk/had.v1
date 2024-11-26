<?php
namespace App\Services\CustomerService\CustomerAuthService;

use App\Http\Resources\V1\App\CustomerResource;
use App\Models\Customer;
use App\Models\VerificationCode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use App\Traits\ApiResponse;  // إضافة التريت

class CustomerRegisterService
{
    use ApiResponse;  // استخدام التريت

    protected $customer_model;
    protected $verify_model;

    public function __construct(Customer $customer_model, VerificationCode $verify_model)
    {
        $this->customer_model = $customer_model;
        $this->verify_model = $verify_model;
    }

    protected function getCustomerByPhone(string $phone): ?Customer
    {
        return $this->customer_model->where('phone_number', $phone)->first();
    }

    public function register($request)
    {
        $customer = $this->getCustomerByPhone($request->phone_number);

        if ($customer) {
            if (!$customer->is_active) {
                return $this->errorResponse('الحساب غير مفعل. يرجى استكمال عملية التحقق.', 409);
            }
            return $this->errorResponse('هذا الرقم لديه حساب بالفعل.', 409);
        }
        $rateLimitKey = $this->getRateLimitKey($request->phone_number);

        if (RateLimiter::tooManyAttempts($rateLimitKey, 1)) {
            return $this->sendRateLimitExceededResponse($rateLimitKey);
        }

        DB::beginTransaction();

        try {
            $customer = $this->createCustomer($request->all());

            $this->sendVerificationCode($customer);

            // $token = $customer->createToken('authToken')->plainTextToken;

            // RateLimiter::hit($rateLimitKey, 120);

            DB::commit();
            $customer->load('addresses');
            return $this->successResponse([  // استخدم التريت هنا
                'message' => 'تم ارسال رمز التأكيد بنجاح',
                'customer' => new CustomerResource($customer),
                // 'access_token' => $token
            ], 'Registration successful', 201);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error($e->getMessage());

            return $this->errorResponse('حدث خطأ في التسجيل الرجاء المحاولة مرة اخرى', code: 500);  // استخدم التريت هنا
        }
    }

    public function resendVerificationCode($request)
    {
        $customer = $this->getCustomerByPhone($request->phone_number);

        if (!$customer) {
            return $this->errorResponse('لا يوجد حساب مسجل بهذا الرقم', 409);  // استخدم التريت هنا
        }

        $rateLimitKey = $this->getRateLimitKey($request->phone_number);

        if (RateLimiter::tooManyAttempts($rateLimitKey, 1)) {
            return $this->sendRateLimitExceededResponse($rateLimitKey);
        }

        DB::beginTransaction();

        try {
            $this->sendVerificationCode($customer);
            RateLimiter::hit($rateLimitKey, 120);
            DB::commit();

            return $this->successResponse(['message' => 'تم ارسال رمز التأكيد بنجاح'], 'Success', 200);  // استخدم التريت هنا
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            return $this->errorResponse('حدث خطأ في ارسال رمز التأكيد', 500);  // استخدم التريت هنا
        }
    }

    protected function getRateLimitKey($phoneNumber)
    {
        return 'send-verification-code:' . $phoneNumber;
    }

    protected function sendRateLimitExceededResponse($rateLimitKey)
    {
        $seconds = RateLimiter::availableIn($rateLimitKey);
        return $this->errorResponse('تم إرسال رمز التأكيد مؤخراً، الرجاء الانتظار ' . $seconds . ' ثانية', 429);  // استخدم التريت هنا
    }

    public function sendVerificationCode(Customer $customer)
    {
        $code = 123456;
        $this->storeVerificationCode($customer, $code);
    }

    private function storeVerificationCode(?Customer $customer, string $code): void
    {
        $customer->verificationCodes()->delete();

        try {
            $this->verify_model->create([
                'verifiable_type' => Customer::class,
                'verifiable_id' => $customer->id,
                'code' => $code,
                'expires_at' => now()->addMinutes(5),
            ]);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            throw new \RuntimeException('حدث خطأ في التسجيل الرجاء المحاولة مرة اخرى' . $e->getMessage(), 500, $e);
        }
    }

    private function generateVerificationCode(): string
    {
        do {
            $code = rand(123456, 999999);
        } while (VerificationCode::where('code', $code)->exists());

        return $code;
    }

    private function createCustomer(array $data): Customer
    {
        try {
            // إنشاء أو تحديث المستخدم
            $customer = $this->customer_model->updateOrCreate(
                ['phone_number' => $data['phone_number']],
                [
                    'name' => $data['name'],
                    'phone_number' => $data['phone_number'],
                    'is_active' => false,
                    'is_suspended' => true,
                ]
            );

            // إنشاء عنوان افتراضي إذا كانت الإحداثيات متوفرة
            if (!empty($data['latitude']) && !empty($data['longitude'])) {
                $customer->addresses()->updateOrCreate(
                    [
                        'latitude' => $data['latitude'],
                        'longitude' => $data['longitude'],
                    ],
                    [
                        'name' => $data['address_name'] ?? 'العنوان 1', // اسم العنوان الافتراضي إذا لم يُرسل
                        'is_default' => true, // يمكن تحديده كافتراضي
                        'location' => is_string($data['location'] ?? '') ? $data['location'] : "",
                    ]
                );
            }

            return $customer;
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            throw new \RuntimeException('حدث خطأ في التسجيل، الرجاء المحاولة مرة أخرى', 500, $e);
        }
    }

    public function verifyVerificationCode(array $requestData)
    {
        $customer = $this->customer_model
            ->where('phone_number', $requestData['phone_number'])
            ->where('is_active', false)
            ->first();

        if (!$customer) {
            return $this->errorResponse('رقم الهاتف غير موجود', 400);  // استخدم التريت هنا
        }

        $verification = $this->verify_model
            ->where('verifiable_type', Customer::class)
            ->where('verifiable_id', $customer->id)
            ->where('code', $requestData['verification_code'])
            ->where('verified', false)
            ->where('expires_at', '>', now())
            ->first();

        if (!$verification) {
            return $this->errorResponse('رمز التحقق غير صالح', 400);  // استخدم التريت هنا
        }
        $token = $customer->createToken('authToken')->plainTextToken;

        $verification->delete();

        $customer->update([
            'is_suspended' => false,
        ]);

        return $this->successResponse(
            [
                'message' => 'تم التحقق بنجاح , قم بإنشاء كلمة مرور جديدة',
                'customer' => new CustomerResource($customer),
                'access_token' => $token
            ],
            'Verification successful.',
            200
        );
    }

    public function setPassword(array $requestData)
    {
        $customer = $this->getCustomerByPhone($requestData['phone_number']);

        if (!$customer) {
            return $this->errorResponse('لا يوجد حساب مسجل بهذا الرقم', 409);  // استخدم التريت هنا
        }

        $customer->update([
            'password' => Hash::make($requestData['password']),
            'is_active' => true
        ]);

        $customer->tokens()->delete();

        return $this->successResponse(
            ['message' => 'تم انشاء كلمة المرور بنجاح'],
            'Password set successfully.',
            200
        );
    }
}
