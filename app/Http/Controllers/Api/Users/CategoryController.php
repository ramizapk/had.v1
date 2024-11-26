<?php

namespace App\Http\Controllers\Api\Users;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\Admin\CategoryResource;
use App\Models\Category;
use App\Models\CategoryVendor;
use App\Models\Vendor;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
class CategoryController extends Controller
{
    use ApiResponse; // استخدام التريت

    public function index()
    {
        $categories = Category::all();
        return $this->successResponse(CategoryResource::collection($categories)); // استخدام successResponse من التريت
    }

    /**
     * إضافة قسم جديد.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'return_able' => 'required|boolean',
            'publish' => 'nullable|boolean',
            'order' => 'nullable|integer',
            'section_id' => 'required|exists:sections,id',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors()); // استخدام errorResponse من التريت
        }


        $category = Category::create([
            'name' => $request->name,
            'return_able' => $request->return_able,
            'publish' => $request->publish,
            'order' => $request->order,
            'section_id' => $request->section_id,
            'created_by' => auth()->id(),
        ]);

        return $this->successResponse(new CategoryResource($category), 'Category created successfully', 201); // استخدام successResponse
    }

    /**
     * عرض قسم معين.
     */
    public function show($id)
    {
        $category = Category::find($id);

        if (!$category) {
            return $this->errorResponse('Category not found', 404); // استخدام errorResponse
        }

        return $this->successResponse(new CategoryResource($category)); // استخدام successResponse
    }

    /**
     * تحديث قسم.
     */
    public function update(Request $request, $id)
    {
        // العثور على التصنيف باستخدام id
        $category = Category::find($id);

        // إذا لم يتم العثور على التصنيف
        if (!$category) {
            return $this->errorResponse('Category not found', 404); // استخدام errorResponse
        }

        // التحقق من البيانات المدخلة (اختياري، إذا كنت بحاجة للتحقق من صحة البيانات)
        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255',
            'return_able' => 'nullable|boolean',
            'publish' => 'nullable|boolean',
            'order' => 'nullable|integer',
            'section_id' => 'nullable|exists:sections,id',
        ]);

        // إذا كانت البيانات المدخلة غير صالحة
        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        // تحديث التصنيف وتعيين updated_by إلى معرف المستخدم الحالي
        $category->update(array_merge($request->all(), [
            'updated_by' => auth()->id(), // تعيين updated_by باستخدام المستخدم الحالي
        ]));

        // إرجاع الاستجابة بنجاح مع البيانات المحدثة
        return $this->successResponse(new CategoryResource($category), 'Category updated successfully');
    }

    /**
     * حذف قسم.
     */
    public function destroy($id)
    {
        $category = Category::find($id);

        if (!$category) {
            return $this->errorResponse('Category not found', 404); // استخدام errorResponse
        }

        $category->delete();
        return $this->successResponse(null, 'Category deleted successfully'); // استخدام successResponse
    }

    /**
     * تحديث ترتيب الأقسام.
     */
    public function updateOrder(Request $request, $id)
    {
        $category = Category::find($id);

        if (!$category) {
            return $this->errorResponse('Category not found', 404); // استخدام errorResponse
        }

        $newOrder = $request->input('order');
        $sectionId = $request->input('section_id');

        $category->updateOrder($id, $newOrder, $sectionId);
        return $this->successResponse(null, 'Category order updated successfully'); // استخدام successResponse
    }


    public function showBySection($sectionId)
    {
        $categories = Category::where('section_id', $sectionId)->get();

        if ($categories->isEmpty()) {
            return $this->errorResponse('No categories found for this section', 404); // إذا لم توجد أقسام
        }

        return $this->successResponse(CategoryResource::collection($categories)); // إرجاع الأقسام عبر section_id
    }


    public function assignCategoryToVendor(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category_id' => 'required|exists:categories,id',
            'vendor_id' => 'required|exists:vendors,id',
            'show_in_menu' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        // التحقق ما إذا كانت الفئة قد تم تعيينها للبائع بالفعل
        $existingAssignment = CategoryVendor::where('category_id', $request->category_id)
            ->where('vendor_id', $request->vendor_id)
            ->first();

        if ($existingAssignment) {
            return $this->errorResponse('Category already assigned to this vendor', 400);
        }

        // تعيين الفئة للبائع
        $assigned = CategoryVendor::create([
            'category_id' => $request->category_id,
            'vendor_id' => $request->vendor_id,
            'show_in_menu' => $request->show_in_menu ?? false,
        ]);

        if ($assigned) {
            return $this->successResponse(null, 'Category assigned to vendor successfully');
        }

        return $this->errorResponse('Failed to assign category to vendor', 400);
    }
    public function removeCategoryFromVendor(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category_id' => 'required|exists:categories,id',
            'vendor_id' => 'required|exists:vendors,id',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        // حذف الرابط بين الفئة والبائع
        $removed = CategoryVendor::where('category_id', $request->category_id)
            ->where('vendor_id', $request->vendor_id)
            ->delete();

        if ($removed) {
            return $this->successResponse(null, 'Category removed from vendor successfully');
        }

        return $this->errorResponse('Category not found for this vendor', 404);
    }


    public function getCategoriesForVendor($vendorId)
    {
        $categories = Vendor::find($vendorId)->categories;  // علاقة Many-to-Many بين البائع والفئات

        if ($categories->isEmpty()) {
            return $this->errorResponse('No categories found for this vendor', 404);
        }

        return $this->successResponse(CategoryResource::collection($categories));
    }



    public function getCategoriesForVendorBySection($vendorId, $sectionId)
    {
        $categories = Vendor::find($vendorId)
            ->categories()
            ->where('section_id', $sectionId)
            ->get();

        if ($categories->isEmpty()) {
            return $this->errorResponse('No categories found for this vendor in this section', 404);
        }

        return $this->successResponse(CategoryResource::collection($categories));
    }


}
