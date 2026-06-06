<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\PredictionResource;
use App\Models\Category;
use App\Models\Prediction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserPredictionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        if (! auth()->user()->is_premium) {
            return $this->premiumRequired();
        }

        $perPage = min((int) $request->query('per_page', 15), 100);

        $paginator = Prediction::with(['category'])
            ->whereHas('category')
            ->where('status', 'active')
            ->when($request->query('category_id'), fn($q, $id) => $q->where('category_id', (int) $id))
            ->when($request->query('title'), fn($q, $title) => $q->where('title', 'like', "%{$title}%"))
            ->latest()
            ->paginate($perPage);

        return $this->paginatedResponse('Predictions retrieved.', PredictionResource::collection($paginator), $paginator);
    }

    public function show(int $id): JsonResponse
    {
        if (! auth()->user()->is_premium) {
            return $this->premiumRequired();
        }

        $prediction = Prediction::with(['category'])->whereHas('category')->findOrFail($id);

        return $this->successResponse('Prediction retrieved.', new PredictionResource($prediction));
    }

    private function premiumRequired(): JsonResponse
    {
        return response()->json([
            'status' => false,
            'message' => 'This feature is available for premium subscribers only.',
            'data' => [
                'premium_required' => true,
            ],
        ], 403);
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
