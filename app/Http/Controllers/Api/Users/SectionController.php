<?php

namespace App\Http\Controllers\Api\Users;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\Admin\SectionResource;
use App\Http\Resources\V1\Admin\VendorResource;
use App\Models\Section;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
class SectionController extends Controller
{
    use ApiResponse;

    public function index()
    {
        $sections = Section::all();
        return $this->successResponse(SectionResource::collection($sections), 'Sections retrieved successfully.');
    }

    /**
     * إضافة قسم جديد.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // التحقق من رفع صورة
            'publish' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors(), 422);
        }

        $data = $request->all();
        $data['created_by'] = auth()->id(); // تحديد المستخدم الذي أضاف القسم

        // معالجة رفع الصورة
        if ($request->hasFile('image')) {
            $imageName = time() . '.' . $request->image->extension();
            // استخدام المسار المحدد من الكلاس الأساسي لتخزين الصورة
            $imagePath = $request->image->storeAs($this->sectionsPath, $imageName, 'public'); // Store in storage path
            $data['image'] = $imagePath; // حفظ المسار الكامل للصورة
        }

        $section = Section::create($data);

        return $this->successResponse(new SectionResource($section), 'Section created successfully.', 201);
    }

    /**
     * تعديل قسم موجود.
     */
    public function update(Request $request, $id)
    {
        $section = Section::find($id);

        if (!$section) {
            return $this->errorResponse('Section not found.', 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // التحقق من رفع صورة
            'publish' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors(), 422);
        }

        $data = $request->all();
        $data['updated_by'] = auth()->id(); // تحديد المستخدم الذي عدل القسم

        // معالجة رفع الصورة الجديدة (إن وجدت)
        if ($request->hasFile('image')) {
            // حذف الصورة القديمة إذا وجدت
            if ($section->image && Storage::exists($section->image)) {
                Storage::delete($section->image); // حذف الصورة القديمة
            }

            $imageName = time() . '.' . $request->image->extension();
            // تخزين الصورة في المسار المحدد
            $imagePath = $request->image->storeAs($this->sectionsPath, $imageName, 'public');
            $data['image'] = $imagePath; // حفظ المسار الكامل للصورة
        }

        $section->update($data);

        return $this->successResponse(new SectionResource($section), 'Section updated successfully.');
    }

    /**
     * حذف قسم.
     */
    public function destroy($id)
    {
        $section = Section::find($id);

        if (!$section) {
            return $this->errorResponse('Section not found.', 404);
        }

        // حذف الصورة عند حذف القسم
        if ($section->image && file_exists(public_path('sections_images/' . $section->image))) {
            unlink(public_path('sections_images/' . $section->image));
        }

        $section->delete();

        return $this->successResponse(null, 'Section deleted successfully.');
    }


    public function getVendors($id)
    {
        $section = Section::with('vendors')->find($id);

        if (!$section) {
            return $this->errorResponse('Section not found.', 404);
        }

        return $this->successResponse(
            VendorResource::collection($section->vendors),
            'Vendors retrieved successfully.'
        );
    }
}
