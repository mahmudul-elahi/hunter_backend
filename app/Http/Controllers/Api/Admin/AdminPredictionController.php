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
use App\Services\NotificationService;
use App\Services\WinRateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminPredictionController extends Controller
{
    public function __construct(
        private readonly WinRateService $winRateService,
        private readonly NotificationService $notificationService,
    ) {}

    public function overview(): JsonResponse
    {
        $totalWins = Prediction::where('status', 'win')->count();
        $resolved = Prediction::whereIn('status', ['win', 'loss'])->count();
        $overallWinRate = $resolved > 0 ? round(($totalWins / $resolved) * 100, 2) : 0;

        return $this->successResponse('Prediction overview retrieved.', [
            'total_records' => Prediction::count(),
            'active_predictions' => Prediction::where('status', 'active')->count(),
            'total_win' => $totalWins,
            'overall_win_rate' => $overallWinRate,
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = min($request->integer('per_page', 15), 100);

        $paginator = Prediction::with(['category', 'creator'])
            ->whereHas('category')
            ->when($request->filled('search'), fn ($q) => $q->where('title', 'like', "%{$request->string('search')}%"))
            ->when($request->filled('category'), fn ($q) => $q->whereHas('category', fn ($q) => $q->where('name', 'like', "%{$request->string('category')}%")))
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->latest()
            ->paginate($perPage);

        return $this->paginatedResponse('Predictions retrieved.', PredictionResource::collection($paginator), $paginator);
    }

    public function store(StorePredictionRequest $request): JsonResponse
    {
        $prediction = Prediction::create(array_merge($request->validated(), ['created_by' => Auth::id()]));

        $prediction->load(['category']);

        $this->notificationService->sendNewPrediction($prediction);

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

            $this->notificationService->sendAdminPredictionResult($prediction);
        }

        return $this->successResponse('Prediction status updated.', new PredictionResource($prediction->load(['category'])));
    }
}
