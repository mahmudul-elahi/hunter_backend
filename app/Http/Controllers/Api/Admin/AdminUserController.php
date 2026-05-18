<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminUserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $users = User::whereHas('roles', fn ($q) => $q->where('name', 'user'))
            ->when($request->query('is_premium'), fn ($q, $v) => $q->where('is_premium', filter_var($v, FILTER_VALIDATE_BOOLEAN)))
            ->when($request->query('search'), fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('first_name', 'like', "%$s%")
                    ->orWhere('last_name', 'like', "%$s%")
                    ->orWhere('email', 'like', "%$s%");
            }))
            ->paginate(15);

        return $this->paginatedResponse('Users retrieved.', UserResource::collection($users), $users);
    }

    public function show(int $id): JsonResponse
    {
        $user = User::findOrFail($id);

        return $this->successResponse('User retrieved.', new UserResource($user));
    }

    public function toggleStatus(int $id): JsonResponse
    {
        $user = User::findOrFail($id);

        if ($user->deleted_at) {
            $user->restore();
            $message = 'User activated.';
        } else {
            $user->delete();
            $message = 'User deactivated.';
        }

        return $this->successResponse($message);
    }
}
