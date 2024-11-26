<?php

namespace App\Http\Controllers\Api\Customers;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\App\AdvertisementResource;
use App\Models\Advertisement;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class AdvertisementController extends Controller
{
    use ApiResponse;

    /**
     * 
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function showActiveAdvertisementsOnMainPage()
    {
        // استرجاع الإعلانات النشطة في الصفحة الرئيسية
        $ads = Advertisement::where('placement', 'main_page')
            ->where('status', 'active')
            ->get();

        // التحقق من وجود إعلانات
        if ($ads->isEmpty()) {
            return $this->errorResponse('No active advertisements found for the main page', 404);
        }

        // إرجاع الإعلانات بنجاح
        return $this->successResponse(AdvertisementResource::collection($ads), 'Active advertisements on the main page retrieved successfully.');
    }

    /**
     * عرض الإعلانات النشطة في صفحة قسم معين.
     *
     * @param  int  $sectionId
     * @return \Illuminate\Http\JsonResponse
     */
    public function showActiveAdvertisementsInSection($sectionId)
    {
        // استرجاع الإعلانات النشطة في القسم المحدد
        $ads = Advertisement::where('placement', 'specific_section')
            ->where('section_id', $sectionId)
            ->where('status', 'active')
            ->get();

        // التحقق من وجود إعلانات
        if ($ads->isEmpty()) {
            return $this->errorResponse('No active advertisements found for this section', 404);
        }

        // إرجاع الإعلانات بنجاح
        return $this->successResponse(AdvertisementResource::collection($ads), 'Active advertisements in the section retrieved successfully.');
    }
}
