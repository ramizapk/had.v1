<?php

namespace App\Http\Controllers\Api\Customers;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\App\ServicesResource;
use App\Http\Resources\V1\App\ServiceTypesResource;
use App\Models\Service;
use App\Models\ServiceType;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    use ApiResponse;

    public function getAllServiceTypes()
    {
        $serviceTypes = ServiceType::all();
        return $this->successResponse(ServiceTypesResource::collection($serviceTypes), 'All service types retrieved successfully.');
    }

    public function getServicesByServiceType($serviceTypeId)
    {
        $serviceType = ServiceType::find($serviceTypeId);

        if (!$serviceType) {
            return $this->errorResponse('Parent service type not found.', 404);
        }

        $services = Service::where('service_type_id', $serviceTypeId)->get();
        return $this->successResponse(ServicesResource::collection($services), 'Services retrieved successfully.');
    }
}
