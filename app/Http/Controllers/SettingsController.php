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
     * Returns merged shop profile (User) + billing settings (Setting).
     */
    public function show(Request $request): JsonResponse
    {
        $user     = $request->user();
        $settings = $user->setting ?? Setting::create(['user_id' => $user->id]);

        $merged = [
            // ── Shop Profile (from User) ──────────────────────────────────────
            'shop_name'      => $user->shop_name,
            'owner_name'     => $user->owner_name,
            'mobile_number'  => $user->mobile_number,
            'shop_address'   => $user->shop_address,
            'business_type'  => $user->business_type,
            'gst_number'     => $user->gst_number,

            // ── Billing / Invoice Settings (from Setting) ─────────────────────
            'invoice_prefix'       => $settings->invoice_prefix,
            'invoice_start_number' => $settings->invoice_start_number,
            'currency'             => $settings->currency        ?? 'INR',
            'currency_symbol'      => $settings->currency_symbol ?? '₹',
            'default_tax_rate'     => $settings->default_tax_rate ?? 0,
            'show_tax_on_invoice'  => $settings->show_tax_on_invoice ?? false,
            'invoice_footer_note'  => $settings->invoice_footer_note,
        ];

        return $this->successResponse($merged, 'Settings retrieved successfully');
    }

    /**
     * PUT /api/settings
     *
     * Saves shop profile fields to User model AND
     * billing/invoice fields to Setting model.
     */
    public function update(UpdateSettingsRequest $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validated();

        // ── Update User fields ────────────────────────────────────────────────
        $userFields = array_filter([
            'shop_name'     => $data['shop_name']     ?? null,
            'owner_name'    => $data['owner_name']    ?? null,
            'shop_address'  => $data['shop_address']  ?? null,
            'business_type' => $data['business_type'] ?? null,
            'gst_number'    => $data['gst_number']    ?? null,
        ], fn ($v) => $v !== null);

        if (!empty($userFields)) {
            $user->update($userFields);
        }

        // ── Update Setting fields ─────────────────────────────────────────────
        $settingFields = array_filter([
            'invoice_prefix'       => $data['invoice_prefix']       ?? null,
            'invoice_start_number' => $data['invoice_start_number'] ?? null,
            'currency'             => $data['currency']             ?? null,
            'currency_symbol'      => $data['currency_symbol']      ?? null,
            'default_tax_rate'     => $data['default_tax_rate']     ?? null,
            'show_tax_on_invoice'  => isset($data['show_tax_on_invoice']) ? $data['show_tax_on_invoice'] : null,
            'invoice_footer_note'  => $data['invoice_footer_note']  ?? null,
        ], fn ($v) => $v !== null);

        Setting::updateOrCreate(['user_id' => $user->id], $settingFields);

        // Return refreshed merged data
        $user->refresh();
        $settings = $user->setting()->first();

        return $this->successResponse([
            'shop_name'      => $user->shop_name,
            'owner_name'     => $user->owner_name,
            'mobile_number'  => $user->mobile_number,
            'shop_address'   => $user->shop_address,
            'business_type'  => $user->business_type,
            'gst_number'     => $user->gst_number,

            'invoice_prefix'       => $settings?->invoice_prefix,
            'invoice_start_number' => $settings?->invoice_start_number,
            'currency'             => $settings?->currency ?? 'INR',
            'currency_symbol'      => $settings?->currency_symbol ?? '₹',
            'default_tax_rate'     => $settings?->default_tax_rate ?? 0,
            'show_tax_on_invoice'  => $settings?->show_tax_on_invoice ?? false,
            'invoice_footer_note'  => $settings?->invoice_footer_note,
        ], 'Settings updated successfully');
    }
}
