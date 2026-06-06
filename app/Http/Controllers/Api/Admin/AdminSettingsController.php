<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Http\Requests\Profile\UpdateAdminProfileRequest;
use App\Http\Resources\AdminResource;
use App\Models\AdminSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class AdminSettingsController extends Controller
{
    public function profile(): JsonResponse
    {
        return $this->successResponse('Profile retrieved.', new AdminResource(Auth::user()));
    }

    public function updateProfile(UpdateAdminProfileRequest $request): JsonResponse
    {
        $user = Auth::user();
        $data = $request->safe()->except('avatar');

        if ($request->hasFile('avatar')) {
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }

            $data['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }

        $user->update($data);

        return $this->successResponse('Profile updated.', new AdminResource($user->fresh()));
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
        $settings = AdminSetting::firstOrCreate(
            [],
            ['new_subscription' => true, 'payment_failed' => true, 'prediction_result' => true],
        );

        return $this->successResponse('Notification settings retrieved.', $settings);
    }

    public function updateNotificationSettings(Request $request): JsonResponse
    {
        $data = $request->validate([
            'new_subscription' => ['sometimes', 'boolean'],
            'payment_failed' => ['sometimes', 'boolean'],
            'prediction_result' => ['sometimes', 'boolean'],
        ]);

        $settings = AdminSetting::firstOrCreate([]);
        $settings->update($data);

        return $this->successResponse('Notification settings updated.', $settings->fresh());
    }
}
