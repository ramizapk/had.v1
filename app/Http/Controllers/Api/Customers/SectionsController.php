<?php

namespace App\Http\Controllers\Api\Customers;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\App\CategoryResource;
use App\Http\Resources\V1\App\SectionResource;
use App\Http\Resources\V1\App\VendorResource;
use App\Models\Section;
use App\Models\Vendor;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class SectionsController extends Controller
{
    use ApiResponse;

    public function getSections()
    {
        // جلب الأقسام المنشورة فقط
        $sections = Section::where('publish', 1)->get();

        // استخدام الريسورس لتحويل البيانات
        return $this->successResponse(SectionResource::collection($sections), 'Sections retrieved successfully.');
    }

    public function getVendorsBySection(Request $request, $sectionId)
    {
        // جلب الفيندورات بناءً على القسم مع الباجينيشن
        $vendors = Vendor::where('publish', 1)
            ->where('section_id', $sectionId)
            ->paginate($request->get('per_page', 10)); // عدد العناصر في كل صفحة (افتراضي 10)

        if ($vendors->isEmpty()) {
            return $this->errorResponse('No vendors found for this section.', 404);
        }

        // إرجاع البيانات مع إضافة الباجينيشن
        return $this->successResponse(
            [
                'data' => VendorResource::collection($vendors),  // هنا تُمَرر الـ VendorResource
                'pagination' => [
                    'current_page' => $vendors->currentPage(),
                    'last_page' => $vendors->lastPage(),
                    'per_page' => $vendors->perPage(),
                    'total' => $vendors->total(),
                ],
            ],
            'Vendors retrieved successfully.'
        );
    }

    public function getCategoriesByVendor($vendorId)
    {
        $categories = Vendor::find($vendorId)
            ->categories()
            ->where('publish', 1) // الشرط الأول: الفئة منشورة
            ->wherePivot('show_in_menu', 1) // الشرط الثاني: تظهر في القائمة
            ->get();

        if ($categories->isEmpty()) {
            return $this->errorResponse('No categories found for this vendor', 404);
        }

        return $this->successResponse(CategoryResource::collection($categories), 'Categories retrieved successfully.');
    }



    public function getCategoriesForVendorBySection($sectionId, $vendorId)
    {
        $categories = Vendor::find($vendorId)
            ->categories()
            ->where('section_id', $sectionId) // الشرط حسب القسم
            ->where('publish', 1) // الشرط الأول: الفئة منشورة
            ->wherePivot('show_in_menu', 1) // الشرط الثاني: تظهر في القائمة
            ->get();

        if ($categories->isEmpty()) {
            return $this->errorResponse('No categories found for this vendor in this section', 404);
        }

        // return $this->successResponse(CategoryResource::collection($categories));
        return $this->successResponse(CategoryResource::collection($categories), 'Categories retrieved successfully.');
    }



}
