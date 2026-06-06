<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'avatar' => $this->avatar ? url(Storage::url($this->avatar)) : null,
            'date_of_birth' => $this->date_of_birth?->toDateString(),
            'location' => $this->location,
            'gender' => $this->gender,
            'is_premium' => $this->is_premium,
            'onboarding_completed' => $this->onboarding_completed,
            'is_active' => $this->is_active,
            'subscription' => $this->resolveSubscription(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function resolveSubscription(): ?array
    {
        $subscription = $this->subscriptions->first();

        return [
            'type' => $subscription?->status ?? 'none',
            'product_id' => $subscription?->revenuecat_product_id,
            'store' => $subscription?->store,
            'expires_at' => $subscription?->expires_at?->toIso8601String(),
        ];
    }
}
