<?php

namespace App\Http\Resources;

use App\Models\Subscription;
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
        $subscription = $this->subscriptions->sortByDesc('expires_at')->first();

        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'avatar' => $this->avatar ? url(Storage::url($this->avatar)) : null,
            'date_of_birth' => $this->date_of_birth?->toDateString(),
            'location' => $this->location,
            'gender' => $this->gender,
            'is_premium' => $this->hasActivePremiumAccess($subscription),
            'onboarding_completed' => $this->onboarding_completed,
            'is_active' => $this->is_active,
            'subscription' => $this->resolveSubscription($subscription),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveSubscription(?Subscription $subscription): array
    {
        return [
            'type' => $this->resolveSubscriptionType($subscription),
            'product_id' => $subscription?->revenuecat_entitlement_id,
            'store' => $subscription?->store,
            'price' => $subscription?->price,
            'purchased_at' => $subscription?->purchased_at?->toIso8601String(),
            'expires_at' => $subscription?->expires_at?->toIso8601String(),
        ];
    }

    /**
     * A subscription is only "active" while its period is still running;
     * a lapsed "active" subscription is reported as "expired".
     */
    private function resolveSubscriptionType(?Subscription $subscription): string
    {
        if (! $subscription) {
            return 'none';
        }

        if ($subscription->isActive()) {
            return 'active';
        }

        if ($subscription->status === 'active') {
            return 'expired';
        }

        return $subscription->status;
    }

    /**
     * Premium access is revoked the moment the subscription period ends,
     * without waiting for the RevenueCat EXPIRATION webhook to arrive.
     */
    private function hasActivePremiumAccess(?Subscription $subscription): bool
    {
        if (! $this->is_premium) {
            return false;
        }

        // Flagged premium without a subscription record (e.g. manually comped).
        if (! $subscription) {
            return true;
        }

        return $subscription->expires_at === null || $subscription->expires_at->isFuture();
    }
}
