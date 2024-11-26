<?php

namespace App\Http\Controllers\Api\Users;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\Admin\ServicesResource;
use App\Http\Resources\V1\Admin\ServiceTypesResource;
use App\Models\Service;
use App\Models\ServiceType;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    use ApiResponse;

    // **دوال الخدمات (Service)**


    public function getAllServices()
    {
        $services = Service::get();
        return $this->successResponse(ServicesResource::collection($services), 'تم جلب جميع الخدمات');
    }

    // إضافة خدمة جديدة
    public function addService(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'image' => 'nullable|image',
            'whatsapp_link' => 'nullable|url',
            'service_type_id' => 'required|exists:service_types,id',
        ]);

        $service = new Service([
            'name' => $request->name,
            'description' => $request->description,
            'image' => $request->image ? $request->file('image')->store($this->servicesPath, 'public') : null,
            'whatsapp_link' => $request->whatsapp_link,
            'service_type_id' => $request->service_type_id,
        ]);

        $service->save();

        return $this->successResponse(new ServicesResource($service), 'تم إضافة الخدمة بنجاح', 201);
    }

    // تعديل خدمة
    public function updateService(Request $request, $id)
    {
        $service = Service::find($id);

        if (!$service) {
            return $this->errorResponse('الخدمة غير موجودة', 404);
        }

        $request->validate([
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image',
            'whatsapp_link' => 'nullable|url',
            'service_type_id' => 'nullable|exists:service_types,id',
        ]);

        $service->name = $request->name ?: $service->name;
        $service->description = $request->description ?: $service->description;
        $service->image = $request->image ? $request->file('image')->store($this->servicesPath, 'public') : $service->image;
        $service->whatsapp_link = $request->whatsapp_link ?: $service->whatsapp_link;
        $service->service_type_id = $request->service_type_id ?: $service->service_type_id;

        $service->save();

        return $this->successResponse(new ServicesResource($service), 'تم تعديل الخدمة بنجاح');
    }

    // حذف خدمة
    public function deleteService($id)
    {
        $service = Service::find($id);

        if (!$service) {
            return $this->errorResponse('الخدمة غير موجودة', 404);
        }

        $service->delete();

        return $this->successResponse([], 'تم حذف الخدمة بنجاح');
    }

    // **دوال أنواع الخدمات (ServiceType)**

    // عرض جميع أنواع الخدمات
    public function getAllServiceTypes()
    {
        $serviceTypes = ServiceType::all();
        return $this->successResponse(ServiceTypesResource::collection($serviceTypes), 'تم جلب جميع أنواع الخدمات');
    }

    // إضافة نوع خدمة جديدة
    public function addServiceType(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'image' => 'nullable|image',
        ]);

        $serviceType = new ServiceType([
            'name' => $request->name,
            'description' => $request->description,
            'image' => $request->image ? $request->file('image')->store($this->serviceTypesPath, 'public') : null,
        ]);

        $serviceType->save();

        return $this->successResponse(new ServiceTypesResource($serviceType), 'تم إضافة نوع الخدمة بنجاح', 201);
    }

    // تعديل نوع خدمة
    public function updateServiceType(Request $request, $id)
    {
        $serviceType = ServiceType::find($id);

        if (!$serviceType) {
            return $this->errorResponse('نوع الخدمة غير موجود', 404);
        }

        $request->validate([
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image',
        ]);

        $serviceType->name = $request->name ?: $serviceType->name;
        $serviceType->description = $request->description ?: $serviceType->description;
        $serviceType->image = $request->image ? $request->file('image')->store($this->serviceTypesPath, 'public') : $serviceType->image;

        $serviceType->save();

        return $this->successResponse(new ServiceTypesResource($serviceType), 'تم تعديل نوع الخدمة بنجاح');
    }

    // حذف نوع خدمة
    public function deleteServiceType($id)
    {
        $serviceType = ServiceType::find($id);

        if (!$serviceType) {
            return $this->errorResponse('نوع الخدمة غير موجود', 404);
        }

        $serviceType->delete();

        return $this->successResponse([], 'تم حذف نوع الخدمة بنجاح');
    }



    public function getServicesByServiceType($serviceTypeId)
    {
        $serviceType = ServiceType::find($serviceTypeId);

        if (!$serviceType) {
            return $this->errorResponse('نوع الخدمة الأب غير موجود', 404);
        }

        $services = Service::where('service_type_id', $serviceTypeId)->get();

        return $this->successResponse(
            ServicesResource::collection($services), // استخدام ResourceCollection
            'تم جلب الخدمات بنجاح'
        );
    }
}
