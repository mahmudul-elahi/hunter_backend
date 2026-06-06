<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

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
        'revenuecat_product_id',
        'revenuecat_entitlement_id',
    ];

    public function activeSubscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class)
            ->whereIn('status', ['active', 'trial']);
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
