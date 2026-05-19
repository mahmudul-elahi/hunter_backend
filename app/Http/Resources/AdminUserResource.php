<?php

namespace App\Http\Resources;

use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class AdminUserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $subscription = $this->subscriptions->where('type', 'default')->first();
        $plan = $subscription
            ? SubscriptionPlan::where('stripe_price_id', $subscription->stripe_price)->first()
            : null;

        $status = match (true) {
            $subscription?->trial_ends_at && $subscription->trial_ends_at->isFuture() => 'trial',
            $subscription?->stripe_status === 'active' => 'active',
            $subscription?->ends_at && $subscription->ends_at->isPast() => 'expired',
            $subscription !== null => 'expired',
            default => 'none',
        };

        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'avatar' => $this->avatar ? url(Storage::url($this->avatar)) : null,
            'registered' => $this->created_at?->toDateString(),
            'plan' => $plan?->name,
            'status' => $status,
            'amount' => $plan ? (float) $plan->price : null,
            'promo_code' => $this->promo_code,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
