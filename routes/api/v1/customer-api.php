<?php


use App\Http\Controllers\Api\Customers\AddressController;
use App\Http\Controllers\Api\Customers\AdvertisementController;
use App\Http\Controllers\Api\Customers\FavoritesController;
use App\Http\Controllers\Api\Customers\ProductsController;
use App\Http\Controllers\Api\Customers\ProfileController;
use App\Http\Controllers\Api\Customers\SectionsController;
use App\Http\Controllers\Api\Customers\ServiceController;
use App\Http\Controllers\Api\Customers\VendorFavoritesController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\CustomerAuthController;

Route::prefix('/auth')->controller(CustomerAuthController::class)->group(function () {
    Route::post('/login', 'login')->middleware('guest:sanctum');
    Route::post('/register', 'register')->middleware('guest:sanctum');
    Route::post('/verify-verification-code', 'verifyVerificationCode')->middleware('guest:sanctum');
    Route::post('/resend-verification-code', 'resendVerificationCode')->middleware('auth:sanctum', 'isCustomer');
    Route::post('/set-password', 'setPassword')->middleware('auth:sanctum', 'isCustomer');
    Route::post('/logout', 'logout')->middleware('auth:sanctum', 'isCustomer');
});

Route::prefix('addresses')->controller(AddressController::class)->group(function () {
    // إضافة عنوان جديد
    Route::post('/add', 'handleAddress')->middleware('auth:sanctum', 'isCustomer');

    // عرض جميع العناوين الخاصة بالعميل
    Route::get('/get', 'handleAddress')->middleware('auth:sanctum', 'isCustomer');

    // تعديل العنوان
    Route::put('update/{addressId}', 'handleAddress')->middleware('auth:sanctum', 'isCustomer');

    // حذف العنوان
    Route::delete('remove/{addressId}', 'handleAddress')->middleware('auth:sanctum', 'isCustomer');

    // تعيين العنوان الافتراضي
    Route::patch('setDefault/{addressId}', 'handleAddress')->middleware('auth:sanctum', 'isCustomer');
});


Route::prefix('/app')->middleware(['auth:sanctum', 'isCustomer'])->group(function () {

    Route::controller(ProfileController::class)->group(function () {
        Route::get('/profile', 'getUserProfile');
        Route::post('/profile/update-avatar', 'updateAvatar');
    });


    Route::controller(FavoritesController::class)->group(function () {
        Route::get('/favorites/products', 'index');
        Route::post('/favorites/products/toggle/{productId}', 'toggle');
        Route::delete('/favorites/products/delete/{id}', 'remove');
        Route::delete('/favorites/products/clear', 'clear');
    });


    Route::controller(VendorFavoritesController::class)->group(function () {
        Route::get('/favorites/vendor', 'index');
        Route::post('/favorites/vendor/toggle/{productId}', 'toggle');
        Route::delete('/favorites/vendor/delete/{id}', 'remove');
        Route::delete('/favorites/vendor/clear', 'clear');
    });
});


Route::prefix('/public')->group(function () {

    Route::controller(SectionsController::class)->group(function () {
        Route::get('/sections', 'getSections');
        Route::get('/sections/{sectionsId}/vendors', 'getVendorsBySection');
        Route::get('/vendors/{vendorId}/categories', 'getCategoriesByVendor');
        Route::get('/sections/{sectionsId}/vendors/{vendorId}/categories', 'getCategoriesForVendorBySection');
    });


    Route::controller(ServiceController::class)->group(function () {
        Route::get('/services', 'getAllServiceTypes');
        Route::get('/services/{serviceId}', 'getServicesByServiceType');
    });

    Route::controller(AdvertisementController::class)->group(function () {
        Route::get('/advertisement', 'showActiveAdvertisementsOnMainPage');
        Route::get('/advertisement/sections/{sectionsId}', 'showActiveAdvertisementsInSection');
    });

    Route::controller(ProductsController::class)->group(function () {
        Route::get('/products', 'getAllProducts');
        Route::get('/products/vendors/{vendorId}', 'getProductsByVendor');
        Route::get('/products/vendors/{vendorId}/categories/{categoryId}', 'getProductsByVendorAndCategory');
        Route::get('/products/{productId}', 'getProductById');
    });


});