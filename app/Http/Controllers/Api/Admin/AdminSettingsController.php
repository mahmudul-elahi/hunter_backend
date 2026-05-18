<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Http\Resources\AdminResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AdminSettingsController extends Controller
{
    public function profile(): JsonResponse
    {
        return $this->successResponse('Profile retrieved.', new AdminResource(Auth::user()));
    }

    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        Auth::user()->update($request->validated());

        return $this->successResponse('Profile updated.', new AdminResource(Auth::user()->fresh()));
    }

    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $user = Auth::user();

        if (! Hash::check($request->current_password, $user->password)) {
            return $this->errorResponse('Current password is incorrect.', 422);
        }

        $user->update(['password' => $request->password]);

        return $this->successResponse('Password changed successfully.');
    }

    public function notificationSettings(): JsonResponse
    {
        return $this->successResponse('Notification settings retrieved.', []);
    }

    public function updateNotificationSettings(): JsonResponse
    {
        return $this->successResponse('Notification settings updated.');
    }
}
