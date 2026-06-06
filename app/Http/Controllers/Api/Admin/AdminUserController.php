<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\AdminUserResource;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminUserController extends Controller
{
    public function overview(): JsonResponse
    {
        $baseQuery = fn () => User::whereHas('roles', fn ($q) => $q->where('name', 'user'));

        $totalUsers = $baseQuery()->count();
        $activeUsers = $baseQuery()->where('is_active', true)->count();
        $newToday = $baseQuery()->whereDate('created_at', today())->count();

        return $this->successResponse('User overview retrieved.', [
            'total_users' => $totalUsers,
            'active_users' => $activeUsers,
            'new_today' => $newToday,
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = min($request->integer('per_page', 15), 100);

        $users = User::whereHas('roles', fn ($q) => $q->where('name', 'user'))
            ->with('subscriptions')
            ->when($request->query('is_premium'), fn ($q, $v) => $q->where('is_premium', filter_var($v, FILTER_VALIDATE_BOOLEAN)))
            ->when($request->query('search'), fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('first_name', 'like', "%$s%")
                    ->orWhere('last_name', 'like', "%$s%")
                    ->orWhere('email', 'like', "%$s%");
            }))
            ->paginate($perPage);

        return $this->paginatedResponse('Users retrieved.', AdminUserResource::collection($users), $users);
    }

    public function show(int $id): JsonResponse
    {
        $user = User::with('subscriptions')->findOrFail($id);

        return $this->successResponse('User retrieved.', new UserResource($user));
    }

    public function toggleStatus(int $id): JsonResponse
    {
        $user = User::findOrFail($id);

        if ($user->hasRole('admin')) {
            return $this->errorResponse('Cannot change status of admin users.', 403);
        }

        $user->is_active = ! $user->is_active;
        $user->save();

        $message = $user->is_active ? 'User activated.' : 'User deactivated.';

        return $this->successResponse($message);
    }
}
