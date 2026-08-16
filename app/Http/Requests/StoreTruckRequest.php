<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreTruckRequest extends FormRequest
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
            'plate' => ['required', 'string', 'max:15', 'unique:trucks,plate'],
            'capacity_m3' => ['required', 'numeric', 'gt:0', 'max:50'],
            'is_active' => ['sometimes', 'boolean'],
            'notes' => ['nullable', 'string'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('plate')) {
            $this->merge([
                'plate' => strtoupper((string) $this->input('plate')),
            ]);
        }
    }
}
