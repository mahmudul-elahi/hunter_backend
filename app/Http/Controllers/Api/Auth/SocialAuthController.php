<?php

namespace App\Http\Controllers\Api\Auth;

use App\Exceptions\SocialAuthException;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\SocialAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SocialAuthController extends Controller
{
    public function __construct(private readonly SocialAuthService $socialAuth) {}

    public function googleLogin(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'fcm_token' => ['nullable', 'string', 'max:512'],
        ]);

        return $this->login('google', $validated['token'], $request);
    }

    public function appleLogin(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'fcm_token' => ['nullable', 'string', 'max:512'],
        ]);

        return $this->login('apple', $validated['token'], $request);
    }

    private function login(string $provider, string $token, Request $request): JsonResponse
    {
        try {
            $user = $this->socialAuth->authenticate($provider, $token);
        } catch (SocialAuthException $e) {
            return $this->errorResponse($e->getMessage(), $e->status);
        }

        if ($request->filled('fcm_token')) {
            $user->setActiveDeviceToken($request->input('fcm_token'));
        }

        return $this->respondWithToken($user);
    }

    private function respondWithToken(User $user): JsonResponse
    {
        $jwtToken = auth('api')->login($user);

        return $this->successResponse('Login successful.', [
            'access_token' => $jwtToken,
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl') * 60,
            'user' => [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'is_premium' => $user->is_premium,
                'onboarding_completed' => $user->onboarding_completed,
            ],
        ]);
    }
}
