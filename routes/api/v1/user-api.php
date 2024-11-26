<?php

use App\Http\Controllers\Api\Auth\UserAuthController;
use App\Http\Controllers\Api\Users\AdvertisementController;
use App\Http\Controllers\Api\Users\CategoryController;
use App\Http\Controllers\Api\Users\DeliveryAgentController;
use App\Http\Controllers\Api\Users\ProductController;
use App\Http\Controllers\Api\Users\SectionController;
use App\Http\Controllers\Api\Users\ServiceController;
use App\Http\Controllers\Api\Users\VendorController;
use Illuminate\Support\Facades\Route;

Route::prefix('/auth')->controller(UserAuthController::class)->group(function () {
    // تسجيل المستخدم
    Route::post('/register', 'register')->middleware('guest:sanctum');


    // تسجيل الدخول
    Route::post('/login', 'login')->middleware('guest:sanctum');

    Route::post('/logout', 'logout')->middleware('auth:sanctum');
    // تسجيل الخروج


});


Route::prefix('/delevery-agent')->controller(DeliveryAgentController::class)->group(function () {
    // إضافة مندوب جديد
    Route::post('/add', 'createAgent')->middleware('auth:sanctum', 'isUser');


});


Route::prefix('/sections')->controller(SectionController::class)->middleware(['auth:sanctum', 'isUser'])->group(function () {
    Route::get('/', 'index'); // عرض جميع الأقسام
    Route::post('/', 'store'); // إضافة قسم جديد
    Route::get('/{id}', 'show'); // عرض قسم محدد
    Route::post('/{id}', 'update'); // تعديل قسم موجود
    Route::delete('/{id}', 'destroy'); // حذف قسم
    Route::get('/{id}/vendors', 'getVendors');
});


Route::prefix('/vendors')->controller(VendorController::class)->middleware(['auth:sanctum', 'isUser'])->group(function () {
    Route::get('/', 'index'); // عرض جميع الفيندورز
    Route::post('/', 'store'); // إضافة فيندور جديد
    // Route::get('/{id}', 'show'); // عرض فيندور محدد
    Route::post('/{id}', 'update'); // تعديل بيانات فيندور
    Route::delete('/{id}', 'destroy'); // حذف فيندور
    Route::post('/{id}/work-times', 'updateWorkTimes'); // إضافة أو تحديث أوقات العمل لفيندور
});


Route::prefix('/categories')->controller(CategoryController::class)->middleware(['auth:sanctum', 'isUser'])->group(function () {
    Route::get('/', 'index'); // عرض جميع الأقسام
    Route::post('/', 'store'); // إضافة قسم جديد
    Route::get('/section/{sectionId}', 'showBySection');
    Route::put('/{id}', 'update'); // تعديل قسم
    Route::delete('/{id}', 'destroy'); // حذف قسم
    Route::put('/{id}/update-order', 'updateOrder'); // تحديث ترتيب الأقسام

    Route::prefix('/vendor')->group(function () {
        Route::post('/assign', 'assignCategoryToVendor'); // تعيين الفئة للبائع
        Route::delete('/remove', 'removeCategoryFromVendor'); // حذف الفئة من البائع
        Route::get('/{vendorId}', 'getCategoriesForVendor'); // عرض الفئات للبائع
        Route::get('/{vendorId}/section/{sectionId}', 'getCategoriesForVendorBySection'); // عرض الفئات للبائع عبر القسم
    });
});



Route::prefix('/customisations')->controller(ProductController::class)->middleware(['auth:sanctum', 'isUser'])->group(function () {
    Route::get('/', 'getAllCustomisations');
    Route::post('/', 'createCustomisation');

    Route::put('/{id}', 'updateCustomisation');
    Route::delete('/{id}', 'deleteCustomisation');
    Route::get('/vendor/{vendorId}', 'getCustomisationsByVendor');
});

Route::prefix('/custom-products')->controller(ProductController::class)->middleware(['auth:sanctum', 'isUser'])->group(function () {
    Route::get('/', 'getAllCustomProducts');
    Route::post('/', 'createCustomProduct');

    Route::put('/{id}', 'updateCustomProduct');
    Route::delete('/{id}', 'deleteCustomProduct');
    Route::get('/vendor/{vendor_id}/customisation/{customisation_id}', 'getCustomProductsByVendorAndCustomisation');

});


Route::prefix('/products')->controller(ProductController::class)->middleware(['auth:sanctum', 'isUser'])->group(function () {
    Route::get('/', 'getAllProducts');
    Route::post('/', 'createProduct');
    Route::get('/vendors/{vendorId}', 'getProductsByVendor');
    Route::get('/vendors/{vendorId}/categories/{categoryId}', 'getProductsByVendorAndCategory');
    Route::get('/single/{productId}', 'getProductById');

    // Route::put('/{id}', 'updateCustomisation');
    // Route::delete('/{id}', 'deleteCustomisation');
    // Route::get('/vendor/{vendorId}', 'getCustomisationsByVendor');
});

Route::prefix('/services')->controller(ServiceController::class)->middleware(['auth:sanctum', 'isUser'])->group(function () {
    // عرض جميع الخدمات
    Route::get('/', 'getAllServices');

    // إضافة خدمة جديدة
    Route::post('/', 'addService');

    Route::get('/{serviceTypeId}/children', 'getServicesByServiceType');

    // تعديل خدمة
    Route::post('/{id}', 'updateService');

    // حذف خدمة
    Route::delete('/{id}', 'deleteService');

    // **الراوت الفرعي: أنواع الخدمات**
    Route::prefix('/types')->group(function () {
        // عرض جميع أنواع الخدمات
        Route::get('/', 'getAllServiceTypes');

        // إضافة نوع خدمة جديد
        Route::post('/add', 'addServiceType');



        // تعديل نوع خدمة
        Route::post('/update/{id}', 'updateServiceType');

        // حذف نوع خدمة
        Route::delete('/delete/{id}', 'deleteServiceType');
    });
});


Route::prefix('/advertisement')->controller(AdvertisementController::class)->middleware(['auth:sanctum', 'isUser'])->group(function () {
    Route::get('/', 'index');
    Route::post('/', 'store');
    Route::get('/{id}', 'show');
    Route::post('/{id}', 'update');
    Route::delete('/{id}', 'destroy');
});