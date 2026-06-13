<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class UserPredictionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = Auth::user();
        $showWinRate = $user && ($user->is_premium || $user->hasRole('admin'));

        $data = [
            'id' => $this->id,
            'title' => $this->title,
            'scheduled_at' => $this->scheduled_at?->toIso8601String(),
            'confidence_level' => $this->confidence_level,
            'signal' => $this->signal,
            'reason' => $this->reason,
            'detailed_summary' => $this->detailed_summary,
            'status' => $this->status,
            'created_at' => $this->created_at?->toIso8601String(),
        ];

        if ($showWinRate) {
            $data['win_rate'] = $this->win_rate;
        }

        return $data;
    }
}
