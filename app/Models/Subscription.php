<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subscription extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'subscription_plan_id',
        'revenuecat_app_user_id',
        'revenuecat_original_app_user_id',
        'revenuecat_product_id',
        'revenuecat_entitlement_id',
        'store',
        'environment',
        'status',
        'price',
        'currency',
        'purchased_at',
        'trial_ends_at',
        'expires_at',
        'cancelled_at',
        'billing_issue_at',
        'raw_customer_info',
        'last_event_id',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'purchased_at' => 'datetime',
            'trial_ends_at' => 'datetime',
            'expires_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'billing_issue_at' => 'datetime',
            'raw_customer_info' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }

    public function isActive(): bool
    {
        return in_array($this->status, ['active', 'trial'], true)
            && ($this->expires_at === null || $this->expires_at->isFuture());
    }
}
