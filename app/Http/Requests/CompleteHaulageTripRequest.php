<?php

namespace App\Http\Requests;

use App\Enums\ProductionStage;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CompleteHaulageTripRequest extends FormRequest
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
            'stage' => ['required', Rule::enum(ProductionStage::class)],
            'affects_stock' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'stage' => $this->input('stage', ProductionStage::QuarryToPrimary->value),
            'affects_stock' => $this->has('affects_stock')
                ? $this->boolean('affects_stock')
                : true,
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'stage.required' => 'Escolha o destino da descarga.',
            'stage.enum' => 'O destino da descarga é inválido.',
        ];
    }

    public function destination(): ProductionStage
    {
        return ProductionStage::from((string) $this->validated('stage'));
    }

    public function shouldEnterStock(): bool
    {
        return (bool) $this->validated('affects_stock');
    }
}
