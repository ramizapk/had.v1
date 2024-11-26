<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\CustomerRequest\CustomerLoginRequest;
use App\Http\Requests\CustomerRequest\CustomerRegisterRequest;
use App\Services\CustomerService\CustomerAuthService\CustomerLoginService;
use App\Services\CustomerService\CustomerAuthService\CustomerRegisterService;

// use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;

class CustomerAuthController extends Controller
{
    // use ApiResponseTrait;

    protected $loginService;
    protected $registerService;

    public function __construct(CustomerLoginService $loginService, CustomerRegisterService $registerService)
    {
        $this->loginService = $loginService;
        $this->registerService = $registerService;
    }

    /**
     * تسجيل الدخول
     */
    public function login(CustomerLoginRequest $request)
    {
        // Benchmark::dd(fn () => $this->CustomerLoginService->login($request));
        return $this->loginService->login($request);
    }

    /**
     * التسجيل
     */
    public function register(CustomerRegisterRequest $request)
    {
        return $this->registerService->register($request);
    }

    public function verifyVerificationCode(Request $request)
    {
        $validation = $request->validate([
            'phone_number' => 'required|string|digits:9|starts_with:7|exists:customers,phone_number',
            'verification_code' => 'required|numeric|digits:6',
        ]);

        return $this->registerService->verifyVerificationCode($validation);
    }

    /**
     * إعادة إرسال رمز التحقق
     */
    public function resendVerificationCode(Request $request)
    {

        $request->validate([
            'phone_number' => 'required|string|digits:9|starts_with:7|exists:customers,phone_number',
        ]);

        return $this->registerService->resendVerificationCode($request);
    }



    /**
     * إعداد كلمة المرور
     */
    public function setPassword(Request $request)
    {

        $request = $request->validate([
            'phone_number' => 'required|string|digits:9|starts_with:7|exists:customers,phone_number',
            'password' => 'required|string|min:6',
            'confirm_password' => 'required|same:password',
        ]);


        return $this->registerService->setPassword($request);
    }

    /**
     * تسجيل الخروج
    //  */
    // public function logout(Request $request)
    // {
    //     $request->user()->currentAccessToken()->delete();
    //     return response()->json(['message' => 'Logout successful'], 200);
    // }
}
