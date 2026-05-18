<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Subscription\StorePlanRequest;
use App\Http\Requests\Subscription\UpdatePlanRequest;
use App\Http\Resources\SubscriptionPlanResource;
use App\Models\SubscriptionPlan;
use Illuminate\Http\JsonResponse;

class AdminSubscriptionPlanController extends Controller
{
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
