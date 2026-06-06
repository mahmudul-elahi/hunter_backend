<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionPlanResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'price' => $this->price,
            'billing_period' => $this->billing_period,
            'description' => $this->description,
            'features' => $this->features,
            'is_active' => $this->is_active,
            'revenuecat_product_id' => $this->revenuecat_product_id,
            'revenuecat_entitlement_id' => $this->revenuecat_entitlement_id,
            'active_subscribers' => $this->active_subscribers ?? 0,
        ];
    }
}
