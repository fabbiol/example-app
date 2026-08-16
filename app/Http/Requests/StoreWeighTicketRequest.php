<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreWeighTicketRequest extends FormRequest
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
            'customer_id' => ['required_without:order_id', 'nullable', 'exists:customers,id'],
            'product_id' => ['required_without:order_id', 'nullable', 'exists:products,id'],
            'vehicle_plate' => ['required', 'string', 'max:15'],
            'tare_weight' => ['required', 'numeric', 'min:0'],
            'gross_weight' => ['required', 'numeric', 'gt:0'],
            'weighed_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $tare = $this->input('tare_weight');
                $gross = $this->input('gross_weight');

                if ($tare !== null && $gross !== null && (float) $gross <= (float) $tare) {
                    $validator->errors()->add('gross_weight', 'O peso bruto deve ser maior que a tara.');
                }
            },
        ];
    }
}
