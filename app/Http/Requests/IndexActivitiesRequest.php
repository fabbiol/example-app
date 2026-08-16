<?php

namespace App\Http\Requests;

use App\Enums\ActivityDomain;
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
        return [
            'domain' => ['nullable', Rule::enum(ActivityDomain::class)],
        ];
    }

    public function domain(): ?ActivityDomain
    {
        $domain = $this->validated('domain');

        return is_string($domain) ? ActivityDomain::from($domain) : null;
    }

    protected function prepareForValidation(): void
    {
        $domain = $this->input('domain');

        if ($domain === '' || $domain === 'all') {
            $this->merge(['domain' => null]);
        }
    }
}
