<?php

namespace App\Http\Requests\PromoCode;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePromoCodeRequest extends FormRequest
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
            'code' => ['sometimes', 'string', 'unique:promo_codes,code,'.$this->route('id')],
            'discount' => ['sometimes', 'numeric', 'min:0'],
            'type' => ['sometimes', 'in:percentage,fixed'],
            'max_users' => ['sometimes', 'integer', 'min:1'],
            'status' => ['sometimes', 'in:active,inactive'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ];
    }
}
