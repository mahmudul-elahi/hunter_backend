<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class AdminAuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        $token = Auth::attempt(['email' => $request->email, 'password' => $request->password]);

        if (! $token) {
            return $this->errorResponse('Invalid credentials.', 401);
        }

        $user = Auth::user();

        if (! $user->is_active) {
            Auth::logout();

            return $this->errorResponse('Account is deactivated.', 403);
        }

        if (! $user->hasRole('admin')) {
            Auth::logout();

            return $this->errorResponse('Unauthorized access.', 403);
        }

        return $this->successResponse('Login successful.', [
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl') * 60,
            'user' => [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
            ],
        ]);
    }

    public function logout(): JsonResponse
    {
        Auth::logout();

        return $this->successResponse('Logged out successfully.');
    }

    public function refresh(): JsonResponse
    {
        $token = Auth::refresh();

        return $this->successResponse('Token refreshed.', [
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl') * 60,
        ]);
    }
}
