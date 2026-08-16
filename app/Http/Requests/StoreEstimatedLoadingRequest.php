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
            'customer_id' => ['required_without:order_id', 'nullable', 'exists:customers,id'],
            'product_id' => ['required_without:order_id', 'nullable', 'exists:products,id'],
            'vehicle_plate' => ['required', 'string', 'max:15'],
            'mode' => ['required', Rule::in(['quantity', 'buckets'])],
            'input_unit' => ['required_if:mode,quantity', 'nullable', Rule::enum(ProductUnit::class)],
            'quantity_input' => ['required_if:mode,quantity', 'nullable', 'numeric', 'gt:0'],
            'buckets_count' => ['required_if:mode,buckets', 'nullable', 'integer', 'min:1'],
            'bucket_capacity_m3' => ['nullable', 'numeric', 'gt:0'],
            'loaded_at' => ['nullable', 'date'],
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
                if ($this->input('mode') === 'buckets' && ! $this->filled('buckets_count')) {
                    $validator->errors()->add('buckets_count', 'Informe quantas conchas foram carregadas.');
                }
            },
        ];
    }
}
