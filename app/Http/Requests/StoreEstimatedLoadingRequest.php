<?php

namespace App\Http\Requests;

use App\Enums\ProductUnit;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreEstimatedLoadingRequest extends FormRequest
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
            'order_id' => ['nullable', 'exists:orders,id'],
            'caixa_id' => ['nullable', 'integer'],
            'customer_id' => ['required_without:order_id', 'nullable', 'exists:customers,id'],
            'vehicle_plate' => ['required', 'string', 'max:15'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id', 'distinct'],
            'items.*.input_unit' => ['required', Rule::enum(ProductUnit::class)],
            'items.*.quantity_input' => ['required', 'numeric', 'gt:0'],
            'loaded_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $caixaId = $this->input('caixa_id');

        if ($caixaId === '' || $caixaId === '0') {
            $this->merge(['caixa_id' => null]);
        }

        if ($this->exists('product_id') && ! $this->exists('items')) {
            $this->merge([
                'items' => [[
                    'product_id' => $this->input('product_id'),
                    'input_unit' => $this->input('input_unit'),
                    'quantity_input' => $this->input('quantity_input'),
                ]],
            ]);
        }
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'caixa_id.integer' => 'O número de pedido do caixa é inválido.',
            'items.required' => 'Informe ao menos um produto com quantidade.',
            'items.min' => 'Informe ao menos um produto com quantidade.',
            'items.*.product_id.required' => 'Selecione o produto.',
            'items.*.product_id.distinct' => 'Não repita o mesmo produto no carregamento.',
            'items.*.quantity_input.required' => 'Informe a quantidade estimada.',
            'items.*.quantity_input.gt' => 'A quantidade deve ser maior que zero.',
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($this->filled('order_id') || $this->filled('customer_id')) {
                    return;
                }

                $validator->errors()->add('customer_id', 'Informe um pedido ou selecione o cliente.');
            },
        ];
    }
}
