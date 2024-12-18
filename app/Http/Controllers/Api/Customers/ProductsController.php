<?php

namespace App\Http\Controllers\Api\Customers;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\App\ProductResource;
use App\Models\Product;
use App\Models\Vendor;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
class ProductsController extends Controller
{

    use ApiResponse;
    public function getAllProducts(Request $request)
    {

        $products = Product::with(['images', 'items.customisations'])->paginate($request->get('per_page', 10));
        ;





        $paginationData = [
            'current_page' => $products->currentPage(),
            'total_pages' => $products->lastPage(),
            'per_page' => $products->perPage(),
            'total_items' => $products->total(),
        ];

        return $this->successResponse([
            'data' => ProductResource::collection($products),
            'pagination' => $paginationData,
        ], 'Products retrieved successfully');

    }

    public function getProductsByVendor(Request $request, $vendorId)
    {

        $products = Product::byVendor($vendorId)
            ->withImagesAndCustomisations()
            ->paginate($request->get('per_page', 10));


        if ($products->isEmpty()) {
            return $this->errorResponse('No products found for this vendor.', 404);
        }


        $paginationData = [
            'current_page' => $products->currentPage(),
            'total_pages' => $products->lastPage(),
            'per_page' => $products->perPage(),
            'total_items' => $products->total(),
        ];

        return $this->successResponse([
            'data' => ProductResource::collection($products),
            'pagination' => $paginationData,
        ], 'Products retrieved successfully');
    }


    public function getProductsByVendorAndCategory(Request $request, $vendorId, $categoryId)
    {
        // جلب المنتجات مع التصفح
        $products = Product::byVendor($vendorId)
            ->byCategory($categoryId)
            ->withImagesAndCustomisations()
            ->paginate($request->get('per_page', 10));

        // التحقق من وجود المنتجات
        if ($products->isEmpty()) {
            return $this->errorResponse('No products found for this vendor and category.', 404);
        }

        $paginationData = [
            'current_page' => $products->currentPage(),
            'total_pages' => $products->lastPage(),
            'per_page' => $products->perPage(),
            'total_items' => $products->total(),
        ];

        return $this->successResponse([
            'data' => ProductResource::collection($products),
            'pagination' => $paginationData,
        ], 'Products retrieved successfully');
    }

    public function getProductById($productId)
    {
        // جلب المنتج
        $product = Product::withImagesAndCustomisations()->find($productId);

        // التحقق من وجود المنتج
        if (!$product) {
            return $this->errorResponse('Product not found.', 404);
        }

        // إرجاع البيانات باستخدام Resource
        return $this->successResponse(new ProductResource($product), 'Product retrieved successfully');
    }


    public function getVendorsGallery($vendorId)
    {
        $vendor = Vendor::where('id', $vendorId)->where('is_service_provider', 1)
            ->where('publish', 1)
            ->first();

        if (!$vendor) {
            return $this->errorResponse('Vendor not found or not a service provider.', 404);
        }

        $images = $vendor->images->map(function ($image) {
            return [
                'id' => $image->id,
                'image_url' => $image->image_url ? Storage::url($image->image_url) : null,
            ];
        });

        return $this->successResponse($images, 'Vendor images retrieved successfully.');
    }
}
