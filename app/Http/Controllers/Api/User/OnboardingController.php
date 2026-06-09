<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Onboarding\SaveCategoriesRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class OnboardingController extends Controller
{
    public function saveCategories(SaveCategoriesRequest $request): JsonResponse
    {
        $user = Auth::user();
        $user->preferredCategories()->sync($request->validated('category_ids'));
        $user->update(['onboarding_completed' => true]);

        return $this->successResponse('Preferred categories saved.');
    }
}
