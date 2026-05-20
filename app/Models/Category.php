<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'win_rate',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'win_rate' => 'decimal:2',
        ];
    }

    public function predictions(): HasMany
    {
        return $this->hasMany(Prediction::class);
    }

    public function preferredByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_preferred_categories');
    }
}
