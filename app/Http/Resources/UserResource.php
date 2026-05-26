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
        $subscription = $this->subscription('default');

        $type = match (true) {
            $subscription?->onTrial() => 'trial',
            $subscription?->active() && ! $subscription->onTrial() => 'paid',
            $subscription?->canceled() => 'cancelled',
            $subscription?->ended() => 'ended',
            default => 'none',
        };

        return [
            'type' => $type,
            'trial_ends_at' => $subscription?->trial_ends_at?->toIso8601String(),
            'ends_at' => $subscription?->ends_at?->toIso8601String(),
        ];
    }
}
