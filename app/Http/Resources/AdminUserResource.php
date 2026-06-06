<?php

namespace App\Http\Resources;

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
        $subscription = $this->subscriptions->first();
        $plan = $subscription?->plan;

        $status = $this->is_active ? 'active' : 'deactive';
        $planStatus = match (true) {
            ! $this->is_active => 'deactive',
            $subscription === null => 'none',
            in_array($subscription?->status, ['active', 'trial'], true) => 'running',
            $subscription?->status === 'billing_issue' => 'billing_issue',
            in_array($subscription?->status, ['cancelled', 'expired', 'refunded'], true) => 'expired',
            default => 'expired',
        };

        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'avatar' => $this->avatar ? url(Storage::url($this->avatar)) : null,
            'registered' => $this->created_at?->toDateString(),
            'plan' => $plan?->name,
            'plan_status' => $planStatus,
            'status' => $status,
            'amount' => $plan ? (float) $plan->price : null,
            'subscription' => $subscription ? [
                'status' => $subscription->status,
                'product_id' => $subscription->revenuecat_product_id,
                'store' => $subscription->store,
                'environment' => $subscription->environment,
                'expires_at' => $subscription->expires_at?->toIso8601String(),
            ] : null,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
