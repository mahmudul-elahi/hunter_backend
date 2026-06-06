<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class CategoryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'icon' => $this->icon,
            'image' => $this->image ? Storage::url($this->image) : null,
            'description' => $this->description,
            'win_rate' => $this->win_rate,
            'active_predictions_count' => $this->when(isset($this->active_predictions_count), (int) $this->active_predictions_count),
            'is_active' => $this->is_active,
        ];
    }
}
