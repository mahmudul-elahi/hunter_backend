<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\PredictionResource;
use App\Models\Category;
use App\Models\Prediction;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class AdminDashboardController extends Controller
{
    public function overview(): JsonResponse
    {
        $totalWins = Prediction::where('status', 'win')->count();
        $resolved = Prediction::whereIn('status', ['win', 'loss'])->count();
        $overallWinRate = $resolved > 0 ? round(($totalWins / $resolved) * 100, 2) : 0;

        $totalSubscribers = User::where('is_premium', true)->count();

        $monthlyRevenue = (float) Subscription::whereIn('status', ['active', 'trial'])
            ->whereMonth('updated_at', now()->month)
            ->whereYear('updated_at', now()->year)
            ->sum('price');

        return $this->successResponse('Dashboard overview retrieved.', [
            'overall_win_rate' => $overallWinRate,
            'active_predictions' => Prediction::where('status', 'active')->count(),
            'total_subscribers' => $totalSubscribers,
            'monthly_revenue' => $monthlyRevenue,
        ]);
    }

    public function winRateChart(): JsonResponse
    {
        $data = Category::whereNotNull('win_rate')->get(['id', 'name', 'win_rate']);

        return $this->successResponse('Win rate chart data retrieved.', $data);
    }

    public function recentPredictions(): JsonResponse
    {
        $predictions = Prediction::with(['category'])
            ->whereHas('category')
            ->latest()
            ->limit(10)
            ->get();

        return $this->successResponse('Recent predictions retrieved.', PredictionResource::collection($predictions));
    }
}
