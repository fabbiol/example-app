<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateCrushingCircuitRequest extends FormRequest
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
            'notes' => ['nullable', 'string'],
            'is_active' => ['required', 'boolean'],
            'yields' => ['required', 'array', 'min:1'],
            'yields.*.id' => ['nullable', 'integer', 'exists:crushing_circuit_yields,id'],
            'yields.*.product_id' => ['required', 'integer', 'exists:products,id', 'distinct'],
            'yields.*.group_name' => ['nullable', 'string', 'max:255'],
            'yields.*.percent' => ['required', 'numeric', 'gt:0', 'lte:100'],
            'yields.*.percent_min' => ['nullable', 'numeric', 'gte:0', 'lte:100'],
            'yields.*.percent_max' => ['nullable', 'numeric', 'gte:0', 'lte:100'],
            'yields.*.sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $yields = $this->input('yields', []);
                $total = collect($yields)->sum(fn ($yield) => (float) ($yield['percent'] ?? 0));

                if (abs($total - 100) > 0.05) {
                    $validator->errors()->add(
                        'yields',
                        'A soma dos percentuais deve ser 100%. Atual: '.number_format($total, 3, ',', '.').'%.',
                    );
                }
            },
        ];
    }
}
