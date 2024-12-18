<?php

use App\Http\Controllers\Api\Auth\DeliveryAuthController;
use App\Http\Controllers\Api\DeliveryAgents\DeliveryAgentProfileController;
use App\Http\Controllers\Api\DeliveryAgents\OrdersController;
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


Route::prefix('/orders')->controller(OrdersController::class)->group(function () {

    Route::post('/order-acceptance/{orderId}', 'handleOrderAcceptance')->middleware('auth:sanctum', 'isDelivery');
    Route::post('/update-status/{orderId}', 'updateOrderStatus')->middleware('auth:sanctum', 'isDelivery');
    Route::get('/new', 'getNewOrdersForAgent')->middleware('auth:sanctum', 'isDelivery');
    Route::get('/completed', 'getCompletedOrdersForAgent')->middleware('auth:sanctum', 'isDelivery');
    Route::get('/under-processing', 'getUnderProcessingOrdersForAgent')->middleware('auth:sanctum', 'isDelivery');


});

Route::prefix('/returns')->controller(OrdersController::class)->group(function () {

    Route::post('/return-acceptance/{returnId}', 'handleReturnAcceptance')->middleware('auth:sanctum', 'isDelivery');
    Route::post('/update-status/{returnId}', 'updateReturnStatus')->middleware('auth:sanctum', 'isDelivery');
    Route::get('/new', 'getNewReturnsForAgent')->middleware('auth:sanctum', 'isDelivery');
    Route::get('/completed', 'getCompletedReturnsForAgent')->middleware('auth:sanctum', 'isDelivery');
    Route::get('/under-processing', 'getUnderProcessingReturnsForAgent')->middleware('auth:sanctum', 'isDelivery');


});