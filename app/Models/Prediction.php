<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Prediction extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'category_id',
        'title',
        'scheduled_at',
        'confidence_level',
        'signal',
        'reason',
        'detailed_summary',
        'status',
        'win_rate',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'win_rate' => 'decimal:2',
            'confidence_level' => 'integer',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
