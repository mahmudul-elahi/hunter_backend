<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index(): JsonResponse
    {
        $notifications = Auth::user()->notifications()->latest()->paginate(15);

        return $this->paginatedResponse(
            'Notifications retrieved.',
            NotificationResource::collection($notifications),
            $notifications
        );
    }

    public function markAsRead(string $id): JsonResponse
    {
        $notification = Auth::user()->notifications()->findOrFail($id);
        $notification->update(['read_at' => now()]);

        return $this->successResponse('Notification marked as read.', new NotificationResource($notification));
    }

    public function markAllAsRead(): JsonResponse
    {
        Auth::user()->notifications()->whereNull('read_at')->update(['read_at' => now()]);

        return $this->successResponse('All notifications marked as read.');
    }
}
