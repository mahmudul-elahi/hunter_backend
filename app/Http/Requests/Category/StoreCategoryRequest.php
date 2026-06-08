<?php

namespace App\Http\Requests\Category;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreCategoryRequest extends FormRequest
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
            'icon' => ['required', 'file', 'mimes:svg', 'extensions:svg', 'max:2048'],
            'image' => ['required', 'image', 'mimes:png,jpg,jpeg,webp,svg', 'max:2048'],
            'description' => ['required', 'string'],
            'is_active' => ['boolean'],
        ];
    }
}
