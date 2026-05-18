<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Prediction;

class WinRateService
{
    public function recalculate(int $categoryId): float
    {
        $wins = Prediction::where('category_id', $categoryId)->where('status', 'win')->count();
        $losses = Prediction::where('category_id', $categoryId)->where('status', 'loss')->count();

        $total = $wins + $losses;
        $winRate = $total > 0 ? round(($wins / $total) * 100, 2) : 0;

        Category::where('id', $categoryId)->update(['win_rate' => $winRate]);

        Prediction::where('category_id', $categoryId)
            ->whereIn('status', ['win', 'loss'])
            ->update(['win_rate' => $winRate]);

        return $winRate;
    }
}
