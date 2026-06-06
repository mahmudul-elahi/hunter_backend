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

        $status = $this->is_active ? 'active' : 'deactive';
        $subscriptionStatus = match (true) {
            ! $this->is_active => 'deactive',
            $subscription === null => 'none',
            $subscription?->status === 'active' => 'running',
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
            'subscription_status' => $subscriptionStatus,
            'status' => $status,
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
