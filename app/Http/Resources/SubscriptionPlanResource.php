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
            'billing_every' => $this->billing_every,
            'billing_duration' => $this->billing_duration,
            'description' => $this->description,
            'features' => $this->features,
            'is_active' => $this->is_active,
            'stripe_price_id' => $this->stripe_price_id,
        ];
    }
}
