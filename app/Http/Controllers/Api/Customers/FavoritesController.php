<?php

namespace App\Http\Controllers\Api\Customers;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\App\FavoritesResource;
use App\Models\Favorite;
use App\Models\Product;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
class FavoritesController extends Controller
{
    use ApiResponse;

    // عرض قائمة المفضلة الخاصة بالعميل
    public function index()
    {
        $customer = Auth::user();

        $favorites = Favorite::where('customer_id', $customer->id)
            ->with('product')
            ->get();

        return $this->successResponse(FavoritesResource::collection($favorites), 'Favorites retrieved successfully');
    }

    // إضافة أو إزالة منتج من المفضلة (Toggle)
    public function toggle($productId)
    {
        $customer = Auth::user();


        if (!Product::find($productId)) {
            return $this->errorResponse('Product not found.', 404);
        }

        $favorite = Favorite::where('customer_id', $customer->id)
            ->where('product_id', $productId)
            ->first();

        if ($favorite) {
            // المنتج موجود بالفعل في المفضلة - نحذفه
            $favorite->delete();
            return $this->successResponse(null, 'Product removed from favorites.');
        } else {
            // المنتج غير موجود - نضيفه
            Favorite::create([
                'customer_id' => $customer->id,
                'product_id' => $productId,
            ]);

            return $this->successResponse(null, 'Product added to favorites.');
        }
    }

    // حذف منتج معين من المفضلة
    public function remove($id)
    {
        $customer = Auth::user();


        $favorite = Favorite::where('customer_id', $customer->id)
            ->where('id', $id)
            ->first();

        if (!$favorite) {
            return $this->errorResponse('Product not found in favorites.', 404);
        }

        $favorite->delete();
        return $this->successResponse(null, 'Product removed from favorites.');
    }

    // حذف جميع المفضلة
    public function clear()
    {
        $customer = Auth::user();

        Favorite::where('customer_id', $customer->id)->delete();

        return $this->successResponse(null, 'All favorites cleared.');
    }
}
