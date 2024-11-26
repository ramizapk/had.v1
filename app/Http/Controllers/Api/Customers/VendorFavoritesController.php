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
            $favorite->delete();
            return $this->successResponse(null, 'Vendor removed from favorites.');
        } else {
            $newFavorite = VendorFavorite::create([
                'customer_id' => $customer->id,
                'vendor_id' => $vendorId,
            ]);

            return $this->successResponse(new VendorFavoriteResource($newFavorite), 'Vendor added to favorites.');
        }
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
        return $this->successResponse(null, 'Vendor removed from favorites.');
    }
    // حذف جميع الفيندور المفضل
    public function clear()
    {
        $customer = Auth::user();

        VendorFavorite::where('customer_id', $customer->id)->delete();

        return $this->successResponse(null, 'All vendor favorites cleared.');
    }
}
