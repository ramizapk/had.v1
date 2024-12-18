<?php

namespace App\Http\Controllers\Api\Customers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Models\ApplicationSetting;
class ApplicationSettingController extends Controller
{
    use ApiResponse;

    public function getSettings()
    {
        $settings = ApplicationSetting::first();

        if (!$settings) {
            return $this->errorResponse('No application settings found.', 404);
        }

        return $this->successResponse($settings, 'Application settings retrieved successfully.');
    }
}
