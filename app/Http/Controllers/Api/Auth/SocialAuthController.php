<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class SocialAuthController extends Controller
{
    public function googleLogin(Request $request): JsonResponse
    {
        $request->validate(['access_token' => ['required', 'string']]);

        return $this->handleSocialLogin('google', $request->access_token);
    }

    public function appleLogin(Request $request): JsonResponse
    {
        $request->validate(['identity_token' => ['required', 'string']]);

        return $this->handleSocialLogin('apple', $request->identity_token);
    }

    private function handleSocialLogin(string $provider, string $token): JsonResponse
    {
        try {
            $socialUser = Socialite::driver($provider)->stateless()->userFromToken($token);
        } catch (Throwable) {
            return $this->errorResponse('Invalid or expired token.', 401);
        }

        $user = DB::transaction(function () use ($provider, $socialUser): User {
            $idField = "{$provider}_id";

            $user = User::where($idField, $socialUser->getId())
                ->orWhere('email', $socialUser->getEmail())
                ->first();

            if ($user) {
                $user->update([$idField => $socialUser->getId()]);

                return $user;
            }

            [$firstName, $lastName] = $this->parseName($socialUser->getName());

            $newUser = User::create([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $socialUser->getEmail(),
                'email_verified_at' => now(),
                $idField => $socialUser->getId(),
                'avatar' => $socialUser->getAvatar(),
            ]);

            $newUser->assignRole('user');

            return $newUser;
        });

        if (! $user->is_active) {
            return $this->errorResponse('Account is deactivated.', 403);
        }

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

    /** @return array{0: string, 1: string} */
    private function parseName(?string $fullName): array
    {
        if (! $fullName) {
            return ['', ''];
        }

        $parts = explode(' ', trim($fullName), 2);

        return [$parts[0], $parts[1] ?? ''];
    }
}
