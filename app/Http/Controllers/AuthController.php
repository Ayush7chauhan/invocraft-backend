<?php

namespace App\Http\Controllers;

use App\Models\Otp;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class AuthController extends Controller
{
    /**
     * Send OTP to mobile number
     * OTP will be last 6 digits of mobile number
     */
    public function sendOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'mobile_number' => 'required|string|size:10|regex:/^[0-9]{10}$/',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid mobile number',
                'errors' => $validator->errors()
            ], 422);
        }

        $mobileNumber = $request->mobile_number;
        
        // Generate OTP - last 6 digits of mobile number
        // For 10-digit mobile (9876543210), last 6 digits are positions 4-9 (0-indexed)
        // This gives us: 43210 (5 digits) - but we need 6
        // So we'll take from position 4 with length 6: positions 4-9 = 6 characters
        $otp = substr($mobileNumber, 4, 6);
        
        // Ensure it's exactly 6 digits
        if (strlen($otp) < 6) {
            $otp = str_pad($otp, 6, '0', STR_PAD_LEFT);
        }
        
        // Expires in 5 minutes
        $expiresAt = Carbon::now()->addMinutes(5);

        // Invalidate previous OTPs for this mobile number
        Otp::where('mobile_number', $mobileNumber)
            ->where('is_verified', false)
            ->update(['is_verified' => true]);

        // Create new OTP
        $otpRecord = Otp::create([
            'mobile_number' => $mobileNumber,
            'otp' => $otp,
            'is_verified' => false,
            'expires_at' => $expiresAt,
        ]);

        // In production, send SMS here
        // For now, we'll return the OTP in development
        return response()->json([
            'success' => true,
            'message' => 'OTP sent successfully',
            'data' => [
                'mobile_number' => $mobileNumber,
                // Remove this in production - only for development
                'otp' => config('app.debug') ? $otp : null,
                'expires_in' => 300, // 5 minutes in seconds
            ]
        ], 200);
    }

    /**
     * Verify OTP
     */
    public function verifyOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'mobile_number' => 'required|string|size:10|regex:/^[0-9]{10}$/',
            'otp' => 'required|string|size:6|regex:/^[0-9]{6}$/',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid input',
                'errors' => $validator->errors()
            ], 422);
        }

        $mobileNumber = $request->mobile_number;
        $otp = $request->otp;

        // Find the latest unverified OTP
        $otpRecord = Otp::where('mobile_number', $mobileNumber)
            ->where('otp', $otp)
            ->where('is_verified', false)
            ->latest()
            ->first();

        if (!$otpRecord) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid OTP'
            ], 401);
        }

        if ($otpRecord->isExpired()) {
            return response()->json([
                'success' => false,
                'message' => 'OTP has expired. Please request a new one.'
            ], 401);
        }

        // Mark OTP as verified
        $otpRecord->markAsVerified();

        // Check if user exists
        $user = User::where('mobile_number', $mobileNumber)->first();

        if (!$user) {
            // Create temporary user (registration not complete)
            $user = User::create([
                'mobile_number' => $mobileNumber,
                'is_registration_complete' => false,
            ]);
        }

        // Generate token if user is already registered
        $token = null;
        if ($user->is_registration_complete) {
            $token = base64_encode(json_encode([
                'user_id' => $user->id,
                'mobile_number' => $user->mobile_number,
                'exp' => time() + (30 * 24 * 60 * 60) // 30 days
            ]));
        }

        // Generate token (you can use Sanctum or JWT here)
        // For now, we'll return user data
        return response()->json([
            'success' => true,
            'message' => 'OTP verified successfully',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'mobile_number' => $user->mobile_number,
                    'is_registration_complete' => $user->is_registration_complete,
                ],
                'requires_registration' => !$user->is_registration_complete,
                'token' => $token, // Token only if registration is complete
            ]
        ], 200);
    }

    /**
     * Resend OTP
     */
    public function resendOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'mobile_number' => 'required|string|size:10|regex:/^[0-9]{10}$/',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid mobile number',
                'errors' => $validator->errors()
            ], 422);
        }

        // Call sendOtp logic
        return $this->sendOtp($request);
    }

    /**
     * Update shop details
     */
    public function updateShopDetails(Request $request)
    {
        // Get and normalize input
        $input = $request->all();
        
        // Convert empty strings to null for optional fields
        if (isset($input['shop_name']) && $input['shop_name'] === '') {
            unset($input['shop_name']);
        }
        if (isset($input['shop_address']) && $input['shop_address'] === '') {
            unset($input['shop_address']);
        }
        if (isset($input['business_type']) && $input['business_type'] === '') {
            unset($input['business_type']);
        }

        $validator = Validator::make($input, [
            'user_id' => 'required|integer|exists:users,id',
            'shop_name' => 'sometimes|nullable|string|max:255',
            'owner_name' => 'required|string|min:2|max:255',
            'shop_address' => 'sometimes|nullable|string|max:1000',
            'business_type' => 'sometimes|nullable|string|max:50',
            'is_registration_complete' => 'required|boolean',
        ]);

        // Custom validation for shop_address - if provided and not null, must be at least 10 characters
        $validator->sometimes('shop_address', 'min:10', function ($input) {
            return isset($input['shop_address']) && $input['shop_address'] !== null && $input['shop_address'] !== '';
        });

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::findOrFail($input['user_id']);

        // Prepare update data - only include fields that are set
        $updateData = [
            'owner_name' => $input['owner_name'],
            'is_registration_complete' => $input['is_registration_complete'],
        ];

        // Only add optional fields if they are provided
        if (isset($input['shop_name'])) {
            $updateData['shop_name'] = $input['shop_name'];
        }
        if (isset($input['shop_address'])) {
            $updateData['shop_address'] = $input['shop_address'];
        }
        if (isset($input['business_type'])) {
            $updateData['business_type'] = $input['business_type'];
        }

        $user->update($updateData);

        // Generate JWT token (simple token for now, you can use Laravel Sanctum or JWT library)
        $token = base64_encode(json_encode([
            'user_id' => $user->id,
            'mobile_number' => $user->mobile_number,
            'exp' => time() + (30 * 24 * 60 * 60) // 30 days
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Shop details updated successfully',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'mobile_number' => $user->mobile_number,
                    'shop_name' => $user->shop_name,
                    'owner_name' => $user->owner_name,
                    'shop_address' => $user->shop_address,
                    'business_type' => $user->business_type,
                    'is_registration_complete' => $user->is_registration_complete,
                ],
                'token' => $token
            ]
        ], 200);
    }

    /**
     * Verify JWT Token
     */
    public function verifyToken(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Token is required',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $tokenData = json_decode(base64_decode($request->token), true);
            
            if (!$tokenData || !isset($tokenData['user_id']) || !isset($tokenData['exp'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid token'
                ], 401);
            }

            // Check if token is expired
            if ($tokenData['exp'] < time()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token has expired'
                ], 401);
            }

            // Get user
            $user = User::find($tokenData['user_id']);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 401);
            }

            return response()->json([
                'success' => true,
                'message' => 'Token is valid',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'mobile_number' => $user->mobile_number,
                        'shop_name' => $user->shop_name,
                        'owner_name' => $user->owner_name,
                        'shop_address' => $user->shop_address,
                        'business_type' => $user->business_type,
                        'is_registration_complete' => $user->is_registration_complete,
                    ]
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid token'
            ], 401);
        }
    }
}

