<?php

namespace App\Http\Controllers\Api\Users;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\Admin\AdvertisementResource;
use App\Models\Advertisement;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class AdvertisementController extends Controller
{
    use ApiResponse;  // تضمين التريث

    /**
     * Display a listing of the advertisements.
     */
    public function index()
    {
        $advertisements = Advertisement::all(); // يمكنك إضافة التصفية حسب الحاجة
        return $this->successResponse(AdvertisementResource::collection($advertisements), 'Advertisements retrieved successfully.');
    }

    /**
     * Store a newly created advertisement.
     */
    public function store(Request $request)
    {
        // التحقق من المدخلات
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'description' => 'nullable|string',
            'image' => 'required|file|mimes:jpeg,png,jpg,gif|max:2048',
            'type' => 'required|in:internal,external',
            'price' => 'required|numeric',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'placement' => 'required|in:main_page,specific_section',
            'section_id' => 'nullable|exists:sections,id',
            'vendor_id' => 'nullable|exists:vendors,id',
            'product_id' => 'nullable|exists:products,id',
            'target_link' => 'nullable|url',
            'status' => 'nullable|in:pending,active,expired',
        ]);

        // إذا كان التحقق فشل
        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        // رفع الصورة إلى المجلد المحدد من إعدادات config
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store($this->advertisementsPath, 'public'); // استخدام المسار المخزن في المتغير

            // إنشاء الإعلان وحفظه في قاعدة البيانات
            $advertisement = Advertisement::create([
                'name' => $request->name,
                'description' => $request->description,
                'image' => $imagePath,
                'type' => $request->type,
                'price' => $request->price,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'placement' => $request->placement,
                'section_id' => $request->section_id,
                'vendor_id' => $request->vendor_id,
                'product_id' => $request->product_id,
                'target_link' => $request->target_link,
                'status' => $request->status ? $request->status : "pending",
                'created_by' => auth()->user()->id,
            ]);
        } else {
            return $this->errorResponse('Image file is required', 422);
        }

        return $this->successResponse(new AdvertisementResource($advertisement), 'Advertisement created successfully.');
    }

    /**
     * Display the specified advertisement.
     */
    public function show($id)
    {
        $advertisement = Advertisement::find($id);

        if (!$advertisement) {
            return $this->errorResponse('Advertisement not found', 404);
        }

        return $this->successResponse(new AdvertisementResource($advertisement), 'Advertisement retrieved successfully.');
    }

    /**
     * Update the specified advertisement.
     */
    public function update(Request $request, $id)
    {
        $advertisement = Advertisement::find($id);

        if (!$advertisement) {
            return $this->errorResponse('Advertisement not found', 404);
        }

        // التحقق من المدخلات
        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string',
            'description' => 'nullable|string',
            'image' => 'nullable|file|mimes:jpeg,png,jpg,gif|max:2048', // تعديل التحقق للسماح برفع الملفات
            'type' => 'nullable|in:internal,external',
            'price' => 'nullable|numeric',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'placement' => 'nullable|in:main_page,specific_section',
            'section_id' => 'nullable|exists:sections,id',
            'vendor_id' => 'nullable|exists:vendors,id',
            'product_id' => 'nullable|exists:products,id',
            'target_link' => 'nullable|url',
            'status' => 'nullable|in:pending,active,expired',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        // إذا تم تقديم صورة جديدة في الطلب
        if ($request->hasFile('image')) {
            // حذف الصورة القديمة من التخزين إذا كانت موجودة
            $oldImagePath = $advertisement->image; // الحصول على المسار القديم
            if ($oldImagePath && Storage::exists($oldImagePath)) {
                Storage::delete($oldImagePath); // حذف الصورة من التخزين
            }

            // رفع الصورة الجديدة وتخزينها في المسار المحدد
            $imagePath = $request->file('image')->store($this->advertisementsPath, 'public'); // استخدام المسار المخزن في المتغير
        } else {
            // إذا لم يتم تقديم صورة جديدة، استخدم الصورة القديمة
            $imagePath = $advertisement->image;
        }

        // تحديث الإعلان في قاعدة البيانات
        $advertisement->update([
            'name' => $request->name,
            'description' => $request->description,
            'image' => $imagePath, // تحديث المسار الجديد للصورة
            'type' => $request->type,
            'price' => $request->price,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'placement' => $request->placement,
            'section_id' => $request->section_id,
            'vendor_id' => $request->vendor_id,
            'product_id' => $request->product_id,
            'target_link' => $request->target_link,
            'status' => $request->status ? $request->status : "pending",
            'updated_by' => auth()->user()->id, // assuming you're using authentication
        ]);

        return $this->successResponse(new AdvertisementResource($advertisement), 'Advertisement updated successfully.');
    }


    /**
     * Remove the specified advertisement.
     */
    public function destroy($id)
    {
        $advertisement = Advertisement::find($id);

        if (!$advertisement) {
            return $this->errorResponse('Advertisement not found', 404);
        }

        $advertisement->delete();

        return $this->successResponse(null, 'Advertisement deleted successfully.');
    }
}
