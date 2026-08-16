<?php

namespace App\Http\Requests;

use App\Enums\FlowPeriod;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ShowFlowRequest extends FormRequest
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
            'period' => ['required', Rule::enum(FlowPeriod::class)],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ];
    }

    public function period(): FlowPeriod
    {
        return FlowPeriod::from($this->validated('period'));
    }

    /**
     * @return array{from: ?CarbonInterface, to: ?CarbonInterface}
     */
    public function range(): array
    {
        $from = $this->validated('from');
        $to = $this->validated('to');

        return $this->period()->range(
            is_string($from) ? $from : null,
            is_string($to) ? $to : null,
        );
    }

    protected function prepareForValidation(): void
    {
        $period = FlowPeriod::tryFrom((string) $this->input('period'));

        if ($period === null) {
            $period = $this->filled('from') || $this->filled('to')
                ? FlowPeriod::Custom
                : FlowPeriod::Today;
        }

        $this->merge([
            'period' => $period->value,
            'from' => $this->filled('from') ? $this->input('from') : null,
            'to' => $this->filled('to') ? $this->input('to') : null,
        ]);
    }
}
