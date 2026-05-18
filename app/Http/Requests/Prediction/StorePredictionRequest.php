<?php

namespace App\Http\Requests\Prediction;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StorePredictionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'title' => ['required', 'string', 'max:255'],
            'scheduled_at' => ['required', 'date', 'after_or_equal:today'],
            'confidence_level' => ['required', 'integer', 'between:0,100'],
            'signal' => ['required', 'in:home_win,away_win,draw,over,under'],
            'reason' => ['required', 'string'],
            'detailed_summary' => ['nullable', 'string'],
        ];
    }
}
