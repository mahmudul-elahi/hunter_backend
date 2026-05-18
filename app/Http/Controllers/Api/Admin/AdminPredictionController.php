<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Prediction\StorePredictionRequest;
use App\Http\Requests\Prediction\UpdatePredictionRequest;
use App\Http\Requests\Prediction\UpdatePredictionStatusRequest;
use App\Http\Resources\PredictionResource;
use App\Models\Prediction;
use App\Models\User;
use App\Notifications\PredictionResultNotification;
use App\Services\WinRateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class AdminPredictionController extends Controller
{
    public function __construct(private readonly WinRateService $winRateService) {}

    public function index(): JsonResponse
    {
        $paginator = Prediction::with(['category', 'creator'])->latest()->paginate(15);

        return $this->paginatedResponse('Predictions retrieved.', PredictionResource::collection($paginator), $paginator);
    }

    public function store(StorePredictionRequest $request): JsonResponse
    {
        $prediction = Prediction::create(array_merge($request->validated(), ['created_by' => Auth::id()]));

        $prediction->load(['category']);

        return $this->successResponse('Prediction created.', new PredictionResource($prediction), 201);
    }

    public function show(int $id): JsonResponse
    {
        $prediction = Prediction::with(['category'])->findOrFail($id);

        return $this->successResponse('Prediction retrieved.', new PredictionResource($prediction));
    }

    public function update(UpdatePredictionRequest $request, int $id): JsonResponse
    {
        $prediction = Prediction::findOrFail($id);

        if ($prediction->status !== 'active') {
            return $this->errorResponse('Cannot edit a prediction that has already been resolved.', 422);
        }

        $prediction->update($request->validated());

        return $this->successResponse('Prediction updated.', new PredictionResource($prediction->fresh(['category'])));
    }

    public function destroy(int $id): JsonResponse
    {
        Prediction::findOrFail($id)->delete();

        return $this->successResponse('Prediction deleted.');
    }

    public function updateStatus(UpdatePredictionStatusRequest $request, int $id): JsonResponse
    {
        $prediction = Prediction::findOrFail($id);

        if ($prediction->status !== 'active') {
            return $this->errorResponse('Status cannot be changed once a prediction is resolved.', 422);
        }

        $prediction->update(['status' => $request->status]);
        $prediction = $prediction->fresh();

        if (in_array($prediction->status, ['win', 'loss'])) {
            $this->winRateService->recalculate($prediction->category_id);

            User::where('is_premium', true)->each(
                fn (User $user) => $user->notify(new PredictionResultNotification($prediction))
            );
        }

        return $this->successResponse('Prediction status updated.', new PredictionResource($prediction->load(['category'])));
    }
}
