<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class OnboardingController extends Controller
{
    public function saveCategories(Request $request): JsonResponse
    {
        $request->validate([
            'category_ids' => ['required', 'array'],
            'category_ids.*' => ['integer', Rule::exists('categories', 'id')->whereNull('deleted_at')],
        ]);

        $user = Auth::user();
        $user->preferredCategories()->sync($request->category_ids);
        $user->update(['onboarding_completed' => true]);

        return $this->successResponse('Preferred categories saved.');
    }
}
