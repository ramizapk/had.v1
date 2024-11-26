<?php

use App\Http\Controllers\Api\Auth\DeliveryAuthController;
use App\Http\Controllers\Api\DeliveryAgents\DeliveryAgentProfileController;
use Illuminate\Support\Facades\Route;

Route::prefix('/auth')->controller(DeliveryAuthController::class)->group(function () {


    // تسجيل الدخول
    Route::post('login', 'login')->middleware('guest:sanctum');

    Route::post('/logout', 'logout')->middleware('auth:sanctum', 'isDelivery');
    // تسجيل الخروج


});


Route::prefix('/profile')->controller(DeliveryAgentProfileController::class)->group(function () {

    Route::post('/update-location', 'updateLocation')->middleware('auth:sanctum', 'isDelivery');
    Route::post('/update-avatar', 'uploadAvatar')->middleware('auth:sanctum', 'isDelivery');


});