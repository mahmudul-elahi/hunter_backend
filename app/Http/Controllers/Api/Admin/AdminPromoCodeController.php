<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\PromoCode\StorePromoCodeRequest;
use App\Http\Requests\PromoCode\UpdatePromoCodeRequest;
use App\Http\Resources\PromoCodeResource;
use App\Models\PromoCode;
use Illuminate\Http\JsonResponse;

class AdminPromoCodeController extends Controller
{
    public function index(): JsonResponse
    {
        $promoCodes = PromoCode::paginate(15);

        return $this->paginatedResponse('Promo codes retrieved.', PromoCodeResource::collection($promoCodes), $promoCodes);
    }

    public function store(StorePromoCodeRequest $request): JsonResponse
    {
        $promoCode = PromoCode::create($request->validated());

        return $this->successResponse('Promo code created.', new PromoCodeResource($promoCode), 201);
    }

    public function update(UpdatePromoCodeRequest $request, int $id): JsonResponse
    {
        $promoCode = PromoCode::findOrFail($id);
        $promoCode->update($request->validated());

        return $this->successResponse('Promo code updated.', new PromoCodeResource($promoCode));
    }

    public function destroy(int $id): JsonResponse
    {
        PromoCode::findOrFail($id)->delete();

        return $this->successResponse('Promo code deleted.');
    }

    public function toggle(int $id): JsonResponse
    {
        $promoCode = PromoCode::findOrFail($id);
        $promoCode->update([
            'status' => $promoCode->status === 'active' ? 'inactive' : 'active',
        ]);

        return $this->successResponse('Promo code status toggled.', new PromoCodeResource($promoCode));
    }
}
