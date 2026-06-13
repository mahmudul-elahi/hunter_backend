<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function __construct(private readonly NotificationService $notificationService) {}

    public function show(): JsonResponse
    {
        return $this->successResponse('Profile retrieved.', new UserResource(Auth::user()));
    }

    public function update(UpdateProfileRequest $request): JsonResponse
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

        return $this->successResponse('Profile updated.', new UserResource($user->fresh()));
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

    public function deleteAvatar(): JsonResponse
    {
        $user = Auth::user();

        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
            $user->update(['avatar' => null]);
        }

        return $this->successResponse('Avatar deleted.');
    }
}
