<?php

namespace App\Http\Requests\Subscription;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StorePlanRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'billing_period' => ['required', 'in:month,custom'],
            'billing_every' => ['nullable', 'integer', 'min:1'],
            'billing_duration' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'features' => ['required', 'array'],
            'features.*' => ['string'],
            'stripe_price_id' => ['required', 'string'],
            'is_active' => ['boolean'],
        ];
    }
}
