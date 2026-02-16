<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\User;

class AuthenticateToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken() ?? $request->header('Authorization');
        
        if ($token) {
            // Remove 'Bearer ' prefix if present
            $token = str_replace('Bearer ', '', $token);
        }
        
        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication token required'
            ], 401);
        }

        try {
            $decodedToken = json_decode(base64_decode($token), true);

            if (!$decodedToken || !isset($decodedToken['user_id']) || !isset($decodedToken['exp'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid token structure'
                ], 401);
            }

            if ($decodedToken['exp'] < time()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token expired'
                ], 401);
            }

            $user = User::find($decodedToken['user_id']);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 401);
            }

            // Attach user to request
            $request->setUserResolver(function () use ($user) {
                return $user;
            });

            return $next($request);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid token'
            ], 401);
        }
    }
}


