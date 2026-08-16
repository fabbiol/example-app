<?php

namespace App\Http\Requests;

use App\Enums\ProductUnit;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
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
            'code' => ['required', 'string', 'max:50', 'unique:products,code'],
            'unit' => ['required', Rule::enum(ProductUnit::class)],
            'density' => ['required', 'numeric', 'gt:0', 'max:5'],
            'bucket_capacity_m3' => ['required', 'numeric', 'gt:0', 'max:20'],
            'stock_quantity' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
