<?php

namespace App\Http\Requests\Subscription;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'billing_period' => ['sometimes', 'in:monthly,yearly,half_yearly'],
            'description' => ['nullable', 'string'],
            'features' => ['sometimes', 'array'],
            'features.*' => ['string'],
            'revenuecat_product_id' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('subscription_plans', 'revenuecat_product_id')->ignore($this->route('id')),
            ],
            'revenuecat_entitlement_id' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ];
    }
}
