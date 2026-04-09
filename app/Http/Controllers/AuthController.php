<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\SendOtpRequest;
use App\Http\Requests\Auth\SetupShopRequest;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Models\Otp;
use App\Models\Setting;
use App\Models\User;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    use ApiResponse;

    // ─── OTP: Send ────────────────────────────────────────────────────────────

    /**
     * POST /api/send-otp
     *
     * OTP = last 6 digits of the 10-digit mobile number (positions 4-9).
     * Auth flow: mobile-only, no passwords.
     */
    public function sendOtp(SendOtpRequest $request): JsonResponse
    {
        $mobile = $request->validated()['mobile_number'];

        // OTP = characters at position 4 (0-indexed) through 9 = 6 chars
        $otp = substr($mobile, 4, 6);
        if (strlen($otp) < 6) {
            $otp = str_pad($otp, 6, '0', STR_PAD_LEFT);
        }

        $expiresAt = Carbon::now()->addMinutes(5);

        // Invalidate all previous pending OTPs for this mobile
        Otp::where('mobile_number', $mobile)
            ->where('is_verified', false)
            ->update(['is_verified' => true]);

        Otp::create([
            'mobile_number' => $mobile,
            'otp'           => $otp,
            'is_verified'   => false,
            'expires_at'    => $expiresAt,
        ]);

        // In production: dispatch SMS job here.
        return $this->successResponse([
            'mobile_number' => $mobile,
            'otp'           => config('app.debug') ? $otp : null, // hide in production
            'expires_in'    => 300,
        ], 'OTP sent successfully');
    }

    // ─── OTP: Verify ──────────────────────────────────────────────────────────

    /**
     * POST /api/verify-otp
     *
     * Verifies the OTP. If user is not registered yet, returns
     * requires_registration=true with no token (frontend routes to setup-shop).
     * If already registered, returns the Bearer token immediately.
     */
    public function verifyOtp(VerifyOtpRequest $request): JsonResponse
    {
        $data   = $request->validated();
        $mobile = $data['mobile_number'];
        $otp    = $data['otp'];

        $otpRecord = Otp::where('mobile_number', $mobile)
            ->where('otp', $otp)
            ->where('is_verified', false)
            ->latest()
            ->first();

        if (!$otpRecord) {
            return $this->unauthorizedResponse('Invalid OTP.');
        }

        if ($otpRecord->isExpired()) {
            return $this->unauthorizedResponse('OTP has expired. Please request a new one.');
        }

        $otpRecord->markAsVerified();

        $user = User::where('mobile_number', $mobile)->first();
        if (!$user) {
            $user = User::create([
                'mobile_number'             => $mobile,
                'is_registration_complete'  => false,
            ]);
        }

        $token = null;
        if ($user->is_registration_complete) {
            $token = $this->generateToken($user);
        }

        return $this->successResponse([
            'user' => [
                'id'                        => $user->id,
                'mobile_number'             => $user->mobile_number,
                'is_registration_complete'  => $user->is_registration_complete,
            ],
            'requires_registration' => !$user->is_registration_complete,
            'token'                 => $token,
        ], 'OTP verified successfully');
    }

    // ─── OTP: Resend ──────────────────────────────────────────────────────────

    /**
     * POST /api/resend-otp
     */
    public function resendOtp(SendOtpRequest $request): JsonResponse
    {
        return $this->sendOtp($request);
    }

    // ─── Setup Shop ───────────────────────────────────────────────────────────

    /**
     * POST /api/update-shop-details
     *
     * Completes registration for a new user and issues the Bearer token.
     */
    public function updateShopDetails(SetupShopRequest $request): JsonResponse
    {
        $data = $request->validated();

        // Strip empty strings from optional fields so they don't overwrite existing values
        foreach (['shop_name', 'shop_address', 'business_type', 'gst_number'] as $field) {
            if (isset($data[$field]) && $data[$field] === '') {
                unset($data[$field]);
            }
        }

        DB::beginTransaction();
        try {
            $user = User::findOrFail($data['user_id']);

            $updateData = [
                'owner_name'                => $data['owner_name'],
                'is_registration_complete'  => $data['is_registration_complete'],
            ];

            foreach (['shop_name', 'shop_address', 'business_type', 'gst_number'] as $field) {
                if (array_key_exists($field, $data)) {
                    $updateData[$field] = $data[$field];
                }
            }

            $user->update($updateData);

            // Bootstrap default settings for the shop on first-time setup
            if ($data['is_registration_complete'] && !$user->setting()->exists()) {
                Setting::create(['user_id' => $user->id]);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->serverErrorResponse();
        }

        $token = $this->generateToken($user->fresh());

        return $this->successResponse([
            'user'  => [
                'id'                        => $user->id,
                'mobile_number'             => $user->mobile_number,
                'shop_name'                 => $user->shop_name,
                'owner_name'                => $user->owner_name,
                'shop_address'              => $user->shop_address,
                'business_type'             => $user->business_type,
                'gst_number'                => $user->gst_number,
                'is_registration_complete'  => $user->is_registration_complete,
            ],
            'token' => $token,
        ], 'Shop details updated successfully');
    }

    // ─── Token: Verify ────────────────────────────────────────────────────────

    /**
     * POST /api/verify-token
     */
    public function verifyToken(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors(), 'Token is required');
        }

        try {
            $payload = json_decode(base64_decode($request->token), true);

            if (!$payload || !isset($payload['user_id'], $payload['exp'])) {
                return $this->unauthorizedResponse('Invalid token.');
            }

            if ($payload['exp'] < time()) {
                return $this->unauthorizedResponse('Token has expired.');
            }

            $user = User::find($payload['user_id']);
            if (!$user) {
                return $this->unauthorizedResponse('User not found.');
            }

            return $this->successResponse([
                'user' => [
                    'id'                        => $user->id,
                    'mobile_number'             => $user->mobile_number,
                    'shop_name'                 => $user->shop_name,
                    'owner_name'                => $user->owner_name,
                    'shop_address'              => $user->shop_address,
                    'business_type'             => $user->business_type,
                    'gst_number'                => $user->gst_number,
                    'is_registration_complete'  => $user->is_registration_complete,
                ],
            ], 'Token is valid');
        } catch (\Throwable) {
            return $this->unauthorizedResponse('Invalid token.');
        }
    }

    // ─── Dev Only ─────────────────────────────────────────────────────────────

    /**
     * POST /api/dev/auto-login  (local env only)
     */
    public function devAutoLogin(Request $request): JsonResponse
    {
        if (!app()->environment('local')) {
            return $this->unauthorizedResponse();
        }

        $mobile = '9999999999';
        $user   = User::firstOrCreate(
            ['mobile_number' => $mobile],
            [
                'is_registration_complete' => true,
                'owner_name'               => 'Dev Admin',
                'shop_name'                => 'Dev Shop',
            ]
        );

        return $this->successResponse([
            'user'  => [
                'id'                        => $user->id,
                'mobile_number'             => $user->mobile_number,
                'is_registration_complete'  => $user->is_registration_complete,
            ],
            'requires_registration' => false,
            'token'                 => $this->generateToken($user),
        ], 'Dev auto-login successful');
    }

    // ─── Private Helpers ──────────────────────────────────────────────────────

    /**
     * Generate the base64-encoded bearer token.
     * Token is valid for 30 days.
     */
    private function generateToken(User $user): string
    {
        return base64_encode(json_encode([
            'user_id'       => $user->id,
            'mobile_number' => $user->mobile_number,
            'exp'           => time() + (30 * 24 * 60 * 60),
        ]));
    }
}
