<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminSetting extends Model
{
    protected $fillable = [
        'new_subscription',
        'payment_failed',
        'prediction_result',
        'promo_code_used',
    ];

    protected function casts(): array
    {
        return [
            'new_subscription' => 'boolean',
            'payment_failed' => 'boolean',
            'prediction_result' => 'boolean',
            'promo_code_used' => 'boolean',
        ];
    }
}
