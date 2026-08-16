<?php

namespace App\Http\Requests;

use App\Enums\OrderStatus;
use App\Enums\ProductUnit;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderRequest extends FormRequest
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
            'customer_id' => ['required', 'exists:customers,id'],
            'product_id' => ['required', 'exists:products,id'],
            'quantity_input' => ['required', 'numeric', 'gt:0'],
            'input_unit' => ['required', Rule::enum(ProductUnit::class)],
            'status' => ['sometimes', Rule::enum(OrderStatus::class)],
            'destination' => ['nullable', 'string', 'max:255'],
            'vehicle_plate' => ['nullable', 'string', 'max:15'],
            'scheduled_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'quantity_input.required' => 'Informe a quantidade estimada do pedido.',
            'quantity_input.gt' => 'A quantidade deve ser maior que zero.',
            'input_unit.required' => 'Informe a unidade (m³ ou t).',
        ];
    }
}
