<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreLoaderEstimatedLoadingRequest extends FormRequest
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
            'vehicle_plate' => ['required', 'string', 'max:15'],
            'quantity_m3' => ['required', 'numeric', 'gt:0', 'max:99999'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'quantity_m3.required' => 'Informe a quantidade em m³ carregada.',
            'quantity_m3.gt' => 'A quantidade em m³ deve ser maior que zero.',
            'quantity_m3.max' => 'A quantidade em m³ é alta demais. Verifique o valor informado.',
            'vehicle_plate.required' => 'Informe a placa do caminhão.',
        ];
    }
}
