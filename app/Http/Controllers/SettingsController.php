<?php

namespace App\Http\Controllers;

use App\Http\Requests\Settings\UpdateSettingsRequest;
use App\Models\Setting;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    use ApiResponse;

    /**
     * GET /api/settings
     *
     * Returns shop settings. Creates default settings if none exist.
     */
    public function show(Request $request): JsonResponse
    {
        $user     = $request->user();
        $settings = $user->setting ?? Setting::create(['user_id' => $user->id]);

        return $this->successResponse($settings, 'Settings retrieved successfully');
    }

    /**
     * PUT /api/settings
     *
     * Upserts shop settings.
     */
    public function update(UpdateSettingsRequest $request): JsonResponse
    {
        $user = $request->user();

        $settings = Setting::updateOrCreate(
            ['user_id' => $user->id],
            $request->validated()
        );

        return $this->successResponse($settings->fresh(), 'Settings updated successfully');
    }
}
