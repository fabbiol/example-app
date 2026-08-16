<?php

namespace App\Http\Requests;

use App\Enums\ProductUnit;
use App\Enums\ProductionMethod;
use App\Enums\ProductionShift;
use App\Enums\ProductionStage;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreProductionEntryRequest extends FormRequest
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
            'product_id' => ['required', 'exists:products,id'],
            'method' => ['required', Rule::enum(ProductionMethod::class)],
            'stage' => ['required', Rule::enum(ProductionStage::class)],
            'truck_id' => ['nullable', 'exists:trucks,id'],
            'trips_count' => ['required_if:method,trips', 'nullable', 'integer', 'min:1'],
            'truck_capacity_m3' => ['nullable', 'numeric', 'gt:0'],
            'input_unit' => ['required_if:method,quantity', 'nullable', Rule::enum(ProductUnit::class)],
            'quantity_input' => ['required_if:method,quantity', 'nullable', 'numeric', 'gt:0'],
            'shift' => ['required', Rule::enum(ProductionShift::class)],
            'produced_on' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
            'apply_circuit' => ['sometimes', 'boolean'],
            'crushing_circuit_id' => ['nullable', 'exists:crushing_circuits,id'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'apply_circuit' => $this->boolean('apply_circuit'),
        ]);
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($this->input('method') === ProductionMethod::Trips->value
                    && ! $this->filled('truck_id')
                    && ! $this->filled('truck_capacity_m3')) {
                    $validator->errors()->add(
                        'truck_capacity_m3',
                        'Selecione um caminhão ou informe a capacidade da caçamba.',
                    );
                }

                if ($this->boolean('apply_circuit')
                    && $this->input('stage') !== ProductionStage::QuarryToPrimary->value) {
                    $validator->errors()->add(
                        'apply_circuit',
                        'A distribuição do circuito só se aplica na etapa Lavra → primário.',
                    );
                }
            },
        ];
    }
}
