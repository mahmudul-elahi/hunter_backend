<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Cashier\Subscription;

class SubscriptionPlan extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'price',
        'billing_period',
        'description',
        'features',
        'is_active',
        'stripe_price_id',
    ];

    public function activeSubscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class, 'stripe_price', 'stripe_price_id')
            ->whereIn('stripe_status', ['active', 'trialing']);
    }

    protected function casts(): array
    {
        return [
            'features' => 'array',
            'is_active' => 'boolean',
            'price' => 'decimal:2',
        ];
    }
}
