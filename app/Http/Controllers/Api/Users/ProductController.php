<?php

namespace App\Http\Controllers\Api\Users;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\Admin\ProductResource;
use App\Models\CustomisationItem;
use App\Models\ProductImage;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\ProductItem;
use App\Models\Customisation;
use App\Models\CustomProduct;
use Log;

class ProductController extends Controller
{
    use ApiResponse;

    // ********** CUSTOMISATION FUNCTIONS **********


    public function getAllCustomisations()
    {
        $customisations = Customisation::all();
        return $this->successResponse($customisations, 'Customisations retrieved successfully');
    }

    // إضافة تخصيص جديد
    public function createCustomisation(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'note' => 'nullable|string',
            'vendor_id' => 'required|exists:vendors,id',
            'is_multi_select' => 'required|boolean',
        ]);

        $customisation = Customisation::create($validated);

        return $this->successResponse($customisation, 'Customisation created successfully', 201);
    }

    // تعديل تخصيص
    public function updateCustomisation(Request $request, $id)
    {
        $customisation = Customisation::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'note' => 'nullable|string',
            'is_multi_select' => 'sometimes|boolean',
        ]);

        $customisation->update($validated);

        return $this->successResponse($customisation, 'Customisation updated successfully');
    }

    // حذف تخصيص
    public function deleteCustomisation($id)
    {
        $customisation = Customisation::findOrFail($id);
        $customisation->delete();

        return $this->successResponse(null, 'Customisation deleted successfully');
    }

    // عرض التخصيصات الخاصة بفيندور معين
    public function getCustomisationsByVendor($vendorId)
    {
        $customisations = Customisation::where('vendor_id', $vendorId)->get();
        return $this->successResponse($customisations, 'Customisations retrieved successfully for the vendor');
    }

    // ********** CUSTOM PRODUCT FUNCTIONS **********

    // عرض جميع المنتجات المخصصة
    public function getAllCustomProducts()
    {
        $customProducts = CustomProduct::all();
        return $this->successResponse($customProducts, 'Custom Products retrieved successfully');
    }

    // إضافة منتج مخصص جديد
    public function createCustomProduct(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'note' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'vendor_id' => 'required|exists:vendors,id',
            'customisation_id' => 'required|exists:customisations,id',
        ]);

        $customProduct = CustomProduct::create($validated);

        return $this->successResponse($customProduct, 'Custom Product created successfully', 201);
    }

    // تعديل منتج مخصص
    public function updateCustomProduct(Request $request, $id)
    {
        $customProduct = CustomProduct::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'note' => 'nullable|string',
            'price' => 'sometimes|numeric|min:0',
        ]);

        $customProduct->update($validated);

        return $this->successResponse($customProduct, 'Custom Product updated successfully');
    }

    // حذف منتج مخصص
    public function deleteCustomProduct($id)
    {
        $customProduct = CustomProduct::findOrFail($id);
        $customProduct->delete();

        return $this->successResponse(null, 'Custom Product deleted successfully');
    }

    public function getCustomProductsByVendorAndCustomisation($vendor_id, $customisation_id)
    {
        // التأكد من صحة الفيندور والتخصيص
        $customProducts = CustomProduct::where('vendor_id', $vendor_id)
            ->where('customisation_id', $customisation_id)
            ->get();

        if ($customProducts->isEmpty()) {
            return $this->errorResponse('No custom products found for the given vendor and customisation', 404);
        }

        return $this->successResponse($customProducts, 'Custom Products retrieved successfully');
    }


    // ********** PRODUCT FUNCTIONS **********


    public function createProduct(Request $request)
    {
        $validated = $request->validate([
            'product_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'publish' => 'required|boolean',
            'vendor_id' => 'required|exists:vendors,id',
            'category_id' => 'required|exists:categories,id',
            'images' => 'required|array',
            'images.*.img_file' => 'required|file|mimes:jpeg,png,jpg,gif|max:2048', // الصورة مطلوبة
            'images.*.is_default' => 'required|boolean',
            'items' => 'required|array',
            'items.*.name' => 'required|string|max:255',
            'items.*.description' => 'nullable|string',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.publish' => 'required|boolean',
            'items.*.customisations' => 'nullable|array', // إضافة التحقق من customisations
            'items.*.customisations.*.id' => 'nullable|exists:customisations,id', // التحقق من الـ ID إذا كان موجوداً
            'items.*.customisations.*.name' => 'nullable|string|max:255', // التحقق من اسم التخصيص
            'items.*.customisations.*.note' => 'nullable|string', // التحقق من ملاحظة التخصيص
            'items.*.customisations.*.is_multi_select' => 'nullable|boolean', // التحقق من multi select
            'items.*.customisations.*.items' => 'nullable|array', // التحقق من items داخل customisation
            'items.*.customisations.*.items.*.id' => 'nullable|exists:custom_products,id',
            'items.*.customisations.*.items.*.name' => 'nullable|string|max:255', // التحقق من اسم المنتج المخصص
            'items.*.customisations.*.items.*.note' => 'nullable|string', // التحقق من ملاحظة المنتج المخصص
            'items.*.customisations.*.items.*.price' => 'nullable|numeric|min:0', // التحقق من سعر المنتج المخصص
        ]);

        // إنشاء المنتج
        $product = Product::create(array_merge($validated, [
            'created_by' => auth()->id(), // أو Auth::id()
        ]));

        // رفع الصور وحفظ المسار في قاعدة البيانات
        foreach ($validated['images'] as $imageData) {

            $imagePath = $this->productImagesPath;

            if ($imageData['img_file'] instanceof \Illuminate\Http\UploadedFile) {
                $path = $imageData['img_file']->store($imagePath, 'public'); // رفع الصورة إلى مجلد `public`

                ProductImage::create([
                    'img_url' => $path, // حفظ المسار في قاعدة البيانات
                    'is_default' => $imageData['is_default'],
                    'product_id' => $product->id,
                ]);
            } else {
                // التعامل مع الخطأ في حال لم يكن الملف صحيحًا
                return response()->json(['error' => 'Invalid file'], 400);
            }
        }

        // إضافة العناصر (Product Items)
        foreach ($validated['items'] as $itemData) {
            $productItem = ProductItem::create([
                'name' => $itemData['name'],
                'description' => $itemData['description'],
                'price' => $itemData['price'],
                'publish' => $itemData['publish'],
                'product_id' => $product->id,
                'created_by' => auth()->id(),
            ]);

            if (!empty($itemData['customisations'])) {
                foreach ($itemData['customisations'] as $customisationData) {
                    // تحقق من وجود الـ customisation
                    $customisation = isset($customisationData['id'])
                        ? Customisation::find($customisationData['id'])
                        : Customisation::create([
                            'name' => $customisationData['name'],
                            'note' => $customisationData['note'] ?? null,
                            'vendor_id' => $validated['vendor_id'],
                            'is_multi_select' => $customisationData['is_multi_select'],
                        ]);

                    Log::debug('Customisation: ', ['id' => $customisation->id, 'name' => $customisation->name]);

                    $items = [];
                    if (!empty($customisationData['items'])) {
                        foreach ($customisationData['items'] as $customProductData) {
                            $customProduct = isset($customProductData['id'])
                                ? CustomProduct::find($customProductData['id'])
                                : CustomProduct::create([
                                    'name' => $customProductData['name'],
                                    'note' => $customProductData['note'] ?? null,
                                    'price' => $customProductData['price'],
                                    'vendor_id' => $validated['vendor_id'],
                                    'customisation_id' => $customisation->id,
                                ]);

                            Log::debug('Custom Product: ', ['id' => $customProduct->id, 'name' => $customProduct->name]);

                            $items[] = [
                                'id' => $customProduct->id,
                                'name' => $customProduct->name,
                                'price' => $customProduct->price,
                            ];
                        }
                    }

                    // إضافة CustomisationItem
                    CustomisationItem::create([
                        'customisation_id' => $customisation->id,
                        'product_id' => $productItem->id,
                        'items' => json_encode($items),
                    ]);
                }
            }
        }

        return $this->successResponse($product->load(['images', 'items.customisations']), 'Product created successfully', 201);
    }



    // عرض جميع المنتجات
    public function getAllProducts(Request $request)
    {
        // جلب المنتجات مع الصور والعناصر والتخصيصات
        $products = Product::with(['images', 'items.customisations'])->paginate($request->get('per_page', 10));

        // تحويل البيانات لتنسيق JSON الذي تريده

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



    public function toggleProductPublishStatus($id)
    {
        $product = Product::findOrFail($id);

        // تبديل حالة النشر
        $product->publish = !$product->publish;
        $product->save();

        return $this->successResponse($product, 'Product publish status updated successfully', 200);
    }

    public function toggleProductItemPublishStatus($id)
    {
        $productItem = ProductItem::findOrFail($id);

        // تبديل حالة النشر
        $productItem->publish = !$productItem->publish;
        $productItem->save();

        return $this->successResponse($productItem, 'Product item publish status updated successfully', 200);
    }


    public function updateProduct(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'product_name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'publish' => 'sometimes|boolean',
            'vendor_id' => 'sometimes|exists:vendors,id',
            'category_id' => 'sometimes|exists:categories,id',
            'images' => 'nullable|array',
            'images.*.img_file' => 'nullable|file|mimes:jpeg,png,jpg,gif|max:2048',
            'images.*.is_default' => 'nullable|boolean',
        ]);

        $product->update($validated);

        // حذف الصور القديمة من قاعدة البيانات ومن المجلد
        if (!empty($validated['images'])) {
            foreach ($product->images as $image) {
                $path = public_path('storage/' . $image->img_url);
                if (file_exists($path)) {
                    unlink($path); // حذف الملف
                }
                $image->delete(); // حذف السجل من قاعدة البيانات
            }

            // إضافة الصور الجديدة
            foreach ($validated['images'] as $imageData) {
                $imagePath = $this->productImagesPath;

                if ($imageData['img_file'] instanceof \Illuminate\Http\UploadedFile) {
                    $path = $imageData['img_file']->store($imagePath, 'public');

                    ProductImage::create([
                        'img_url' => $path,
                        'is_default' => $imageData['is_default'],
                        'product_id' => $product->id,
                    ]);
                }
            }
        }

        return $this->successResponse($product->load(['images']), 'Product updated successfully', 200);
    }


    public function deleteProductItem($itemId)
    {
        $item = ProductItem::findOrFail($itemId);
        $item->delete();

        return $this->successResponse(null, 'Product Item deleted successfully', 200);
    }
}
