<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PromoCode extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code',
        'discount',
        'type',
        'max_users',
        'used_count',
        'status',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'discount' => 'decimal:2',
        ];
    }

    public function isValid(): bool
    {
        return $this->status === 'active'
            && $this->used_count < $this->max_users
            && ($this->expires_at === null || $this->expires_at->isFuture());
    }
}
