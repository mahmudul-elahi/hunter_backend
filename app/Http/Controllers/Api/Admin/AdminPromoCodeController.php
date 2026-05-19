<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\PromoCode\StorePromoCodeRequest;
use App\Http\Requests\PromoCode\UpdatePromoCodeRequest;
use App\Http\Resources\PromoCodeResource;
use App\Models\PromoCode;
use Illuminate\Http\JsonResponse;
use Laravel\Cashier\Cashier;

class AdminPromoCodeController extends Controller
{
    public function index(): JsonResponse
    {
        $promoCodes = PromoCode::paginate(15);

        return $this->paginatedResponse('Promo codes retrieved.', PromoCodeResource::collection($promoCodes), $promoCodes);
    }

    public function store(StorePromoCodeRequest $request): JsonResponse
    {
        $data = $request->validated();

        try {
            $params = [
                'id' => $data['code'],
                'name' => $data['code'],
                'duration' => 'once',
            ];

            if ($data['type'] === 'percentage') {
                $params['percent_off'] = $data['discount'];
            } else {
                $params['amount_off'] = (int) ($data['discount'] * 100);
                $params['currency'] = config('cashier.currency', 'usd');
            }

            if (! empty($data['max_users'])) {
                $params['max_redemptions'] = $data['max_users'];
            }

            if (! empty($data['expires_at'])) {
                $params['redeem_by'] = strtotime($data['expires_at']);
            }

            Cashier::stripe()->coupons->create($params);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create coupon in Stripe: '.$e->getMessage(), 422);
        }

        $promoCode = PromoCode::create($data);

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
        $promoCode = PromoCode::findOrFail($id);

        try {
            Cashier::stripe()->coupons->delete($promoCode->code);
        } catch (\Exception) {
            // coupon may not exist in Stripe — continue
        }

        $promoCode->delete();

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
