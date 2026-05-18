<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OnboardingController extends Controller
{
    public function saveCategories(Request $request): JsonResponse
    {
        $request->validate([
            'category_ids' => ['required', 'array'],
            'category_ids.*' => ['integer', 'exists:categories,id'],
        ]);

        $user = Auth::user();
        $user->preferredCategories()->sync($request->category_ids);
        $user->update(['onboarding_completed' => true]);

        return $this->successResponse('Preferred categories saved.');
    }
}
