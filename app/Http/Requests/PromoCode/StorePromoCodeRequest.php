<?php

namespace App\Http\Requests\PromoCode;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StorePromoCodeRequest extends FormRequest
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
            'code' => ['required', 'string', 'unique:promo_codes,code'],
            'discount' => ['required', 'numeric', 'min:0'],
            'type' => ['required', 'in:percentage,fixed'],
            'max_users' => ['required', 'integer', 'min:1'],
            'status' => ['sometimes', 'in:active,inactive'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ];
    }
}
