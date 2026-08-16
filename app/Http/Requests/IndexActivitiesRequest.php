<?php

namespace App\Http\Requests;

use App\Enums\ActivityAction;
use App\Enums\ActivityDomain;
use App\Enums\FlowPeriod;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexActivitiesRequest extends FormRequest
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
        $userId = $this->input('user_id');

        return [
            'domain' => ['nullable', Rule::enum(ActivityDomain::class)],
            'action' => ['nullable', Rule::enum(ActivityAction::class)],
            'period' => ['nullable', Rule::enum(FlowPeriod::class)],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'user_id' => $userId === 'system'
                ? ['nullable', 'in:system']
                : ['nullable', 'integer', 'exists:users,id'],
        ];
    }

    public function domain(): ?ActivityDomain
    {
        $domain = $this->validated('domain');

        return is_string($domain) ? ActivityDomain::from($domain) : null;
    }

    public function action(): ?ActivityAction
    {
        $action = $this->validated('action');

        return is_string($action) ? ActivityAction::from($action) : null;
    }

    public function period(): FlowPeriod
    {
        $period = $this->validated('period');

        return is_string($period) ? FlowPeriod::from($period) : FlowPeriod::All;
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

    /**
     * @return int|'system'|null
     */
    public function userFilter(): int|string|null
    {
        $userId = $this->validated('user_id');

        if ($userId === 'system') {
            return 'system';
        }

        if (is_numeric($userId)) {
            return (int) $userId;
        }

        return null;
    }

    protected function prepareForValidation(): void
    {
        $period = FlowPeriod::tryFrom((string) $this->input('period'));
        $userId = $this->input('user_id');
        $domain = $this->input('domain');
        $action = $this->input('action');

        if ($period === null) {
            $period = $this->filled('from') || $this->filled('to')
                ? FlowPeriod::Custom
                : FlowPeriod::All;
        }

        $this->merge([
            'period' => $period->value,
            'domain' => $domain === '' || $domain === 'all' ? null : $domain,
            'action' => $action === '' || $action === 'all' ? null : $action,
            'user_id' => $userId === '' || $userId === 'all' ? null : $userId,
            'from' => $this->filled('from') ? $this->input('from') : null,
            'to' => $this->filled('to') ? $this->input('to') : null,
        ]);
    }
}
