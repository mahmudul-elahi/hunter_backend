<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Subscription\StorePlanRequest;
use App\Http\Requests\Subscription\UpdatePlanRequest;
use App\Http\Resources\SubscriptionPlanResource;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Laravel\Cashier\Cashier;

class AdminSubscriptionPlanController extends Controller
{
    public function overview(): JsonResponse
    {
        $totalSubscribers = User::where('is_premium', true)->count();

        $monthlyRevenue = $this->fetchMonthlyRevenueFromStripe();

        $avgRevenuePerUser = $totalSubscribers > 0
            ? round($monthlyRevenue / $totalSubscribers, 2)
            : 0;

        $churnedThisMonth = DB::table('subscriptions')
            ->where('stripe_status', 'canceled')
            ->whereMonth('updated_at', now()->month)
            ->whereYear('updated_at', now()->year)
            ->count();

        $totalAtStartOfMonth = $totalSubscribers + $churnedThisMonth;
        $churnRate = $totalAtStartOfMonth > 0
            ? round(($churnedThisMonth / $totalAtStartOfMonth) * 100, 2)
            : 0;

        return $this->successResponse('Subscription overview retrieved.', [
            'total_subscribers' => $totalSubscribers,
            'monthly_revenue' => $monthlyRevenue,
            'avg_revenue_per_user' => $avgRevenuePerUser,
            'churn_rate' => $churnRate,
        ]);
    }

    private function fetchMonthlyRevenueFromStripe(): float
    {
        try {
            $stripe = Cashier::stripe();

            $invoices = $stripe->invoices->all([
                'status' => 'paid',
                'created' => [
                    'gte' => now()->startOfMonth()->timestamp,
                    'lte' => now()->endOfMonth()->timestamp,
                ],
                'limit' => 100,
            ]);

            return collect($invoices->data)->sum('amount_paid') / 100;
        } catch (\Throwable) {
            return 0.0;
        }
    }

    public function index(): JsonResponse
    {
        $plans = SubscriptionPlan::paginate(15);

        return $this->paginatedResponse('Plans retrieved.', SubscriptionPlanResource::collection($plans), $plans);
    }

    public function store(StorePlanRequest $request): JsonResponse
    {
        $plan = SubscriptionPlan::create($request->validated());

        return $this->successResponse('Plan created.', new SubscriptionPlanResource($plan), 201);
    }

    public function update(UpdatePlanRequest $request, int $id): JsonResponse
    {
        $plan = SubscriptionPlan::findOrFail($id);
        $plan->update($request->validated());

        return $this->successResponse('Plan updated.', new SubscriptionPlanResource($plan));
    }

    public function destroy(int $id): JsonResponse
    {
        SubscriptionPlan::findOrFail($id)->delete();

        return $this->successResponse('Plan deleted.');
    }
}
