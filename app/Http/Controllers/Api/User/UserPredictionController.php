<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\UserPredictionResource;
use App\Models\Category;
use App\Models\Prediction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserPredictionController extends Controller
{
    public function index(Request $request, int $categoryId): JsonResponse
    {
        if (! auth()->user()->is_premium) {
            return $this->premiumRequired();
        }

        $perPage = min($request->integer('per_page', 15), 100);

        $paginator = Prediction::with(['category'])
            ->whereHas('category')
            ->where('status', 'active')
            ->where('category_id', $categoryId)
            ->when($request->query('title'), fn($q, $title) => $q->where('title', 'like', "%{$title}%"))
            ->latest()
            ->paginate($perPage);

        return $this->paginatedResponse('Predictions retrieved.', UserPredictionResource::collection($paginator), $paginator);
    }

    public function show(int $id): JsonResponse
    {
        if (! auth()->user()->is_premium) {
            return $this->premiumRequired();
        }

        $prediction = Prediction::with(['category'])->whereHas('category')->findOrFail($id);

        return $this->successResponse('Prediction retrieved.', new UserPredictionResource($prediction));
    }

    private function premiumRequired(): JsonResponse
    {
        $errors = [
            'premium_required' => true,
        ];

        return $this->errorResponse('This feature is available for premium subscribers only.', 403, $errors,);
    }

    public function categories(): JsonResponse
    {
        $categories = Category::withCount([
            'predictions as active_predictions_count' => fn($query) => $query->where('status', 'active'),
        ])
            ->where('is_active', true)
            ->get();

        return $this->successResponse('Categories retrieved.', CategoryResource::collection($categories));
    }
}
