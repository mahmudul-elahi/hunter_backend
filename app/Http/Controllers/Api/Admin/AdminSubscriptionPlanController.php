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
        $plans = SubscriptionPlan::withCount([
            'activeSubscriptions as active_subscribers',
        ])->paginate(15);

        return $this->paginatedResponse('Plans retrieved.', SubscriptionPlanResource::collection($plans), $plans);
    }

    public function store(StorePlanRequest $request): JsonResponse
    {
        $data = $request->validated();

        try {
            $stripe = Cashier::stripe();

            $product = $stripe->products->create([
                'name' => $data['name'],
            ]);

            $priceParams = [
                'product' => $product->id,
                'unit_amount' => (int) ($data['price'] * 100),
                'currency' => config('cashier.currency', 'usd'),
                'recurring' => match ($data['billing_period']) {
                    'yearly' => ['interval' => 'year'],
                    'half_yearly' => ['interval' => 'month', 'interval_count' => 6],
                    default => ['interval' => 'month'],
                },
            ];

            $price = $stripe->prices->create($priceParams);

            $data['stripe_price_id'] = $price->id;
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create plan in Stripe: '.$e->getMessage(), 422);
        }

        $plan = SubscriptionPlan::create($data);

        return $this->successResponse('Plan created.', new SubscriptionPlanResource($plan), 201);
    }

    public function update(UpdatePlanRequest $request, int $id): JsonResponse
    {
        $plan = SubscriptionPlan::findOrFail($id);
        $data = $request->validated();

        if (isset($data['name'])) {
            try {
                $stripe = Cashier::stripe();
                $currentPrice = $stripe->prices->retrieve($plan->stripe_price_id);
                $stripe->products->update($currentPrice->product, ['name' => $data['name']]);
            } catch (\Exception $e) {
                return $this->errorResponse('Failed to update plan in Stripe: '.$e->getMessage(), 422);
            }
        }

        $plan->update($data);

        return $this->successResponse('Plan updated.', new SubscriptionPlanResource($plan));
    }

    public function toggleStatus(int $id): JsonResponse
    {
        $plan = SubscriptionPlan::findOrFail($id);
        $plan->update(['is_active' => ! $plan->is_active]);

        $status = $plan->is_active ? 'activated' : 'deactivated';

        return $this->successResponse("Plan {$status}.", new SubscriptionPlanResource($plan));
    }

    public function destroy(int $id): JsonResponse
    {
        $plan = SubscriptionPlan::findOrFail($id);

        $activeSubscribers = DB::table('subscriptions')
            ->where('stripe_price', $plan->stripe_price_id)
            ->whereIn('stripe_status', ['active', 'trialing'])
            ->count();

        if ($activeSubscribers > 0) {
            $plan->update(['is_active' => false]);

            return $this->successResponse(
                "Plan has {$activeSubscribers} active subscriber(s) — marked inactive so no new users can subscribe. It will need to be deleted manually once all subscriptions end.",
                ['is_active' => false, 'active_subscribers' => $activeSubscribers]
            );
        }

        try {
            $price = Cashier::stripe()->prices->retrieve($plan->stripe_price_id);
            Cashier::stripe()->products->update($price->product, ['active' => false]);
        } catch (\Exception) {
            // price may not exist in Stripe — continue
        }

        $plan->delete();

        return $this->successResponse('Plan deleted.');
    }
}
