<?php

namespace App\Http\Controllers\Api\Users;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\Admin\VendorResource;
use App\Http\Resources\V1\Admin\WorkTimeResource;
use App\Models\Vendor;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
class VendorController extends Controller
{
    use ApiResponse;

    /**
     * عرض جميع الفيندورز.
     */
    public function index()
    {
        $vendors = Vendor::with('section', 'workTimes')->get();
        return $this->successResponse(VendorResource::collection($vendors), 'Vendors retrieved successfully.');
    }

    /**
     * إضافة فيندور جديد.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'phone_one' => 'required|string|max:20',
            'phone_two' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'icon' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'publish' => 'nullable|boolean',
            'address' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'section_id' => 'required|integer|exists:sections,id',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors(), 422);
        }

        $data = $request->except('created_by'); // حذف الحقل من البيانات الواردة
        $data['created_by'] = auth()->id(); // إضافة ID المستخدم الحالي

        if ($request->hasFile('icon')) {
            $data['icon'] = $request->file('icon')->store('uploads/vendors', 'public');
        }

        $vendor = Vendor::create($data);
        return $this->successResponse(new VendorResource($vendor), 'Vendor created successfully.', 201);
    }

    /**
     * عرض فيندور محدد.
     */
    public function show($id)
    {
        $vendor = Vendor::with('section', 'workTimes')->find($id);

        if (!$vendor) {
            return $this->errorResponse('Vendor not found.', 404);
        }

        return $this->successResponse(new VendorResource($vendor), 'Vendor retrieved successfully.');
    }

    /**
     * تعديل بيانات الفيندور.
     */
    public function update(Request $request, $id)
    {
        $vendor = Vendor::find($id);

        if (!$vendor) {
            return $this->errorResponse('Vendor not found.', 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255',
            'phone_one' => 'nullable|string|max:20',
            'phone_two' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'icon' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'publish' => 'nullable|boolean',
            'address' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'section_id' => 'nullable|integer|exists:sections,id',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors(), 422);
        }

        $data = $request->except('updated_by'); // حذف الحقل من البيانات الواردة
        $data['updated_by'] = auth()->id(); // إضافة ID المستخدم الحالي

        if ($request->hasFile('icon')) {
            if ($vendor->icon && file_exists(public_path('storage/' . $vendor->icon))) {
                unlink(public_path('storage/' . $vendor->icon));
            }
            $data['icon'] = $request->file('icon')->store('uploads/vendors', 'public');
        }

        $vendor->update($data);

        return $this->successResponse(new VendorResource($vendor), 'Vendor updated successfully.');
    }

    /**
     * حذف فيندور.
     */
    public function destroy($id)
    {
        $vendor = Vendor::find($id);

        if (!$vendor) {
            return $this->errorResponse('Vendor not found.', 404);
        }

        if ($vendor->icon && file_exists(public_path('storage/' . $vendor->icon))) {
            unlink(public_path('storage/' . $vendor->icon));
        }

        $vendor->delete();

        return $this->successResponse(null, 'Vendor deleted successfully.');
    }

    /**
     * إضافة أو تحديث أوقات العمل لفيندور معين.
     */
    public function updateWorkTimes(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'work_times' => 'required|array',
            'work_times.*.day_name' => 'required|string',
            'work_times.*.from' => 'required|string',
            'work_times.*.to' => 'required|string',
            'work_times.*.is_open' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors(), 422);
        }

        $vendor = Vendor::find($id);

        if (!$vendor) {
            return $this->errorResponse('Vendor not found.', 404);
        }

        $vendor->workTimes()->delete();
        $vendor->workTimes()->createMany($request->work_times);

        return $this->successResponse(new VendorResource($vendor), 'Work times updated successfully.');
    }
}
