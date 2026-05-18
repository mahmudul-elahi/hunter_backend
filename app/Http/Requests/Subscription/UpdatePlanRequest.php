<?php

namespace App\Http\Requests\Subscription;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePlanRequest extends FormRequest
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
            'name' => ['sometimes', 'string', 'max:255'],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'billing_period' => ['sometimes', 'in:month,custom'],
            'billing_every' => ['nullable', 'integer', 'min:1'],
            'billing_duration' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'features' => ['sometimes', 'array'],
            'features.*' => ['string'],
            'stripe_price_id' => ['sometimes', 'string'],
            'is_active' => ['boolean'],
        ];
    }
}
