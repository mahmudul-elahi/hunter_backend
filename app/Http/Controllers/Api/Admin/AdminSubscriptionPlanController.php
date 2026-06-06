<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Subscription\StorePlanRequest;
use App\Http\Requests\Subscription\UpdatePlanRequest;
use App\Http\Resources\SubscriptionPlanResource;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\RevenueCatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class AdminSubscriptionPlanController extends Controller
{
    public function __construct(private readonly RevenueCatService $revenueCatService) {}

    public function overview(): JsonResponse
    {
        $totalSubscribers = User::where('is_premium', true)->count();
        $monthlyRevenue = (float) Subscription::whereIn('status', ['active', 'trial'])
            ->whereMonth('updated_at', now()->month)
            ->whereYear('updated_at', now()->year)
            ->sum('price');

        $avgRevenuePerUser = $totalSubscribers > 0
            ? round($monthlyRevenue / $totalSubscribers, 2)
            : 0;

        $churnedThisMonth = Subscription::whereIn('status', ['cancelled', 'expired', 'refunded'])
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
            $this->revenueCatService->createEntitlement([
                'lookup_key' => $data['revenuecat_entitlement_id'] ?? config('revenuecat.premium_entitlement_id'),
                'display_name' => $data['name'].' Entitlement',
            ]);
        } catch (\Throwable $e) {
            Log::warning('RevenueCat: entitlement may already exist, continuing.', [
                'error' => $e->getMessage(),
            ]);
        }

        try {
            $rcProduct = $this->revenueCatService->createProduct([
                'store_identifier' => $data['revenuecat_product_id'],
                'type' => 'subscription',
                'display_name' => $data['name'],
                'subscription' => [
                    'duration' => match ($data['billing_period']) {
                        'monthly' => 'P1M',
                        'half_yearly' => 'P6M',
                        'yearly' => 'P1Y',
                        default => 'P1M',
                    },
                ],
            ]);

            $this->revenueCatService->attachProductsToEntitlement(
                $data['revenuecat_entitlement_id'] ?? config('revenuecat.premium_entitlement_id'),
                [$rcProduct['id']],
            );
        } catch (\Throwable $e) {
            Log::warning('RevenueCat: product/entitlement setup issue, continuing.', [
                'error' => $e->getMessage(),
            ]);
        }

        try {
            $rcOffering = $this->revenueCatService->createOffering([
                'lookup_key' => str($data['name'])->slug('_')->toString().'_offering',
                'display_name' => $data['name'].' Offering',
            ]);

            $this->revenueCatService->createPackage($rcOffering['id'], [
                'lookup_key' => str($data['name'])->slug('_')->toString(),
                'display_name' => $data['name'],
                'products' => [$rcProduct['id'] ?? $data['revenuecat_product_id']],
            ]);
        } catch (\Throwable $e) {
            Log::warning('RevenueCat: offering/package setup issue, continuing.', [
                'error' => $e->getMessage(),
            ]);
        }

        $plan = SubscriptionPlan::create($data);

        return $this->successResponse('Plan created.', new SubscriptionPlanResource($plan), 201);
    }

    public function update(UpdatePlanRequest $request, int $id): JsonResponse
    {
        $plan = SubscriptionPlan::findOrFail($id);
        $data = $request->validated();

        try {
            $rcProduct = $this->revenueCatService->findProductByStoreIdentifier($data['revenuecat_product_id'] ?? $plan->revenuecat_product_id);

            if ($rcProduct) {
                $updateData = [];

                if (isset($data['name']) && $data['name'] !== $plan->name) {
                    $updateData['display_name'] = $data['name'];
                }

                if (isset($data['billing_period']) && $data['billing_period'] !== $plan->billing_period) {
                    $updateData['subscription'] = [
                        'duration' => match ($data['billing_period']) {
                            'monthly' => 'P1M',
                            'half_yearly' => 'P6M',
                            'yearly' => 'P1Y',
                            default => 'P1M',
                        },
                    ];
                }

                if (! empty($updateData)) {
                    $this->revenueCatService->updateProduct($rcProduct['id'], $updateData);
                }
            }

            if (isset($data['name']) && $data['name'] !== $plan->name) {
                $rcEntitlement = $this->revenueCatService->findEntitlementByLookupKey(
                    $data['revenuecat_entitlement_id'] ?? $plan->revenuecat_entitlement_id ?? config('revenuecat.premium_entitlement_id'),
                );

                if ($rcEntitlement) {
                    $this->revenueCatService->updateEntitlement($rcEntitlement['id'], [
                        'display_name' => $data['name'].' Entitlement',
                    ]);
                }

                $oldOfferingKey = str($plan->name)->slug('_')->toString().'_offering';
                $rcOffering = $this->revenueCatService->findOfferingByLookupKey($oldOfferingKey);

                if ($rcOffering) {
                    $this->revenueCatService->updateOffering($rcOffering['id'], [
                        'display_name' => $data['name'].' Offering',
                    ]);
                }
            }
        } catch (\Throwable $e) {
            Log::warning('RevenueCat: plan update sync issue, continuing.', [
                'plan_id' => $id,
                'error' => $e->getMessage(),
            ]);
        }

        $plan->update($data);

        return $this->successResponse('Plan updated.', new SubscriptionPlanResource($plan));
    }

    public function toggleStatus(int $id): JsonResponse
    {
        $plan = SubscriptionPlan::findOrFail($id);
        $plan->update(['is_active' => ! $plan->is_active]);

        try {
            $rcProduct = $this->revenueCatService->findProductByStoreIdentifier($plan->revenuecat_product_id);

            if ($rcProduct) {
                $plan->is_active
                    ? $this->revenueCatService->unarchiveProduct($rcProduct['id'])
                    : $this->revenueCatService->archiveProduct($rcProduct['id']);
            }

            $offeringKey = str($plan->name)->slug('_')->toString().'_offering';
            $rcOffering = $this->revenueCatService->findOfferingByLookupKey($offeringKey);

            if ($rcOffering) {
                $plan->is_active
                    ? $this->revenueCatService->unarchiveOffering($rcOffering['id'])
                    : $this->revenueCatService->archiveOffering($rcOffering['id']);
            }
        } catch (\Throwable $e) {
            Log::warning('RevenueCat: plan status toggle sync issue, continuing.', [
                'plan_id' => $id,
                'error' => $e->getMessage(),
            ]);
        }

        $status = $plan->is_active ? 'activated' : 'deactivated';

        return $this->successResponse("Plan {$status}.", new SubscriptionPlanResource($plan));
    }

    public function destroy(int $id): JsonResponse
    {
        $plan = SubscriptionPlan::withCount([
            'activeSubscriptions as active_subscribers',
        ])->findOrFail($id);

        if ($plan->active_subscribers > 0) {
            $plan->update(['is_active' => false]);

            return $this->successResponse(
                "Plan has {$plan->active_subscribers} active subscriber(s) - marked inactive so no new users can subscribe.",
                ['is_active' => false, 'active_subscribers' => $plan->active_subscribers]
            );
        }

        try {
            $rcProduct = $this->revenueCatService->findProductByStoreIdentifier($plan->revenuecat_product_id);

            if ($rcProduct) {
                $this->revenueCatService->detachProductsFromEntitlement(
                    $plan->revenuecat_entitlement_id ?? config('revenuecat.premium_entitlement_id'),
                    [$rcProduct['id']],
                );

                $this->revenueCatService->archiveProduct($rcProduct['id']);
            }

            $offeringKey = str($plan->name)->slug('_')->toString().'_offering';
            $rcOffering = $this->revenueCatService->findOfferingByLookupKey($offeringKey);

            if ($rcOffering) {
                $this->revenueCatService->archiveOffering($rcOffering['id']);
            }
        } catch (\Throwable $e) {
            Log::warning('RevenueCat: plan deletion sync issue, continuing.', [
                'plan_id' => $id,
                'error' => $e->getMessage(),
            ]);
        }

        $plan->delete();

        return $this->successResponse('Plan deleted.');
    }
}
