<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\Auth\VerifyEmailOtpRequest;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\OtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserAuthController extends Controller
{
    public function __construct(
        private readonly OtpService $otpService,
        private readonly NotificationService $notificationService,
    ) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $user = DB::transaction(function () use ($request): User {
            $user = User::create([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'password' => $request->password,
            ]);

            $user->assignRole('user');

            $this->otpService->send($user->email, 'email_verification');

            return $user;
        });

        return $this->successResponse('Registration successful. Please verify your email.', null, 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $token = Auth::attempt(['email' => $request->email, 'password' => $request->password]);

        if (! $token) {
            return $this->errorResponse('Invalid credentials.', 401);
        }

        $user = Auth::user();

        if (! $user->hasRole('user')) {
            Auth::logout();

            return $this->errorResponse('Unauthorized access.', 403);
        }

        if (! $user->email_verified_at) {
            Auth::logout();

            $this->otpService->send($user->email, 'email_verification');

            return $this->successResponse('Please verify your email. A new OTP has been sent to your email address.', [
                'email_verified' => false,
            ]);
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
                'is_premium' => $user->is_premium,
                'onboarding_completed' => $user->onboarding_completed,
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

    public function verifyEmailOtp(VerifyEmailOtpRequest $request): JsonResponse
    {
        $verified = $this->otpService->verify($request->email, $request->code, 'email_verification');

        if (! $verified) {
            return $this->errorResponse('Invalid or expired OTP.', 422);
        }

        $user = User::where('email', $request->email)->firstOrFail();
        $user->update(['email_verified_at' => now()]);

        $this->notificationService->sendWelcome($user);

        return $this->successResponse('Email verified successfully.');
    }

    public function resendVerificationOtp(ForgotPasswordRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        if (! $user || $user->email_verified_at) {
            return $this->successResponse('If your email exists and is unverified, a new OTP has been sent.');
        }

        $this->otpService->send($request->email, 'email_verification');

        return $this->successResponse('Verification OTP resent to your email.');
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $this->otpService->send($request->email, 'password_reset');

        return $this->successResponse('OTP sent to your email.');
    }

    public function verifyOtp(VerifyOtpRequest $request): JsonResponse
    {
        if (! $this->otpService->check($request->email, $request->code, 'password_reset')) {
            return $this->errorResponse('Invalid or expired OTP.', 422);
        }

        return $this->successResponse('OTP verified successfully.');
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $valid = $this->otpService->verify($request->email, $request->code, 'password_reset');

        if (! $valid) {
            return $this->errorResponse('Invalid or expired OTP.', 422);
        }

        $user = User::where('email', $request->email)->firstOrFail();
        $user->update(['password' => Hash::make($request->password)]);

        $this->notificationService->sendPasswordChanged($user);

        return $this->successResponse('Password reset successfully.');
    }

    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $user = Auth::user();

        if (! Hash::check($request->current_password, $user->password)) {
            return $this->errorResponse('Current password is incorrect.', 422);
        }

        $user->update(['password' => $request->password]);

        $this->notificationService->sendPasswordChanged($user);

        return $this->successResponse('Password changed successfully.');
    }
}
