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
            'billing_period' => ['required', 'in:monthly,yearly,half_yearly'],
            'description' => ['nullable', 'string'],
            'features' => ['required', 'array'],
            'features.*' => ['string'],
            'revenuecat_product_id' => ['nullable', 'string', 'max:255', 'unique:subscription_plans,revenuecat_product_id'],
            'revenuecat_entitlement_id' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->mergeIfMissing([
            'revenuecat_product_id' => str($this->input('name'))->slug('_')->append('_', $this->input('billing_period'))->toString(),
            'revenuecat_entitlement_id' => config('revenuecat.premium_entitlement_id'),
        ]);
    }
}
