<?php

namespace App\Http\Controllers\Api\Customers;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\App\VendorFavoriteResource;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\Vendor;
use App\Models\VendorFavorite;
class VendorFavoritesController extends Controller
{
    use ApiResponse;

    // عرض قائمة الفيندور المفضل
    public function index()
    {
        $customer = Auth::user();

        $favorites = VendorFavorite::where('customer_id', $customer->id)
            ->with('vendor')
            ->get();

        return $this->successResponse(VendorFavoriteResource::collection($favorites), 'Vendor favorites retrieved successfully.');
    }

    // إضافة أو إزالة فيندور من المفضلة (Toggle)
    public function toggle($vendorId)
    {
        $customer = Auth::user();

        if (!Vendor::find($vendorId)) {
            return $this->errorResponse('Vendor not found.', 404);
        }

        $favorite = VendorFavorite::where('customer_id', $customer->id)
            ->where('vendor_id', $vendorId)
            ->first();

        if ($favorite) {
            // إزالة الفيندور من المفضلة
            $favorite->delete();
            $message = 'Vendor removed from favorites.';
        } else {
            // إضافة الفيندور إلى المفضلة
            $newFavorite = VendorFavorite::create([
                'customer_id' => $customer->id,
                'vendor_id' => $vendorId,
            ]);
            $message = 'Vendor added to favorites.';
        }

        // جلب المفضلة الحالية للمستخدم
        $favorites = VendorFavorite::where('customer_id', $customer->id)
            ->with('vendor')
            ->get();

        // إرجاع المفضلات بعد العملية
        return $this->successResponse(
            VendorFavoriteResource::collection($favorites),
            $message
        );
    }


    public function remove($id)
    {
        $customer = Auth::user();

        // البحث عن المفضلة الخاصة بالفيندور
        $favorite = VendorFavorite::where('customer_id', $customer->id)
            ->where('id', $id)
            ->first();

        if (!$favorite) {
            return $this->errorResponse('Vendor not found in favorites.', 404);
        }

        // حذف المفضلة
        $favorite->delete();

        // جلب المفضلة الحالية للمستخدم بعد الحذف
        $favorites = VendorFavorite::where('customer_id', $customer->id)
            ->with('vendor')
            ->get();

        return $this->successResponse(
            VendorFavoriteResource::collection($favorites),
            'Vendor removed from favorites.'
        );
    }

    // حذف جميع الفيندورات من المفضلة
    public function clear()
    {
        $customer = Auth::user();

        // حذف جميع الفيندورات المفضلة
        VendorFavorite::where('customer_id', $customer->id)->delete();

        // جلب المفضلة الحالية للمستخدم بعد الحذف
        $favorites = VendorFavorite::where('customer_id', $customer->id)
            ->with('vendor')
            ->get();

        return $this->successResponse(
            VendorFavoriteResource::collection($favorites),
            'All vendor favorites cleared.'
        );
    }
}
