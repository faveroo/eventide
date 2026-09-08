<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\In;

class DetectionRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('project'));
    }

    /** @return array<string, array<int, string|In>> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'event_type' => ['required', 'string', 'max:255'],
            'threshold' => ['required', 'integer', 'min:1', 'max:100000'],
            'window_seconds' => ['required', 'integer', 'min:1', 'max:604800'],
            'severity' => ['required', Rule::in(['low', 'medium', 'high', 'critical'])],
            'enabled' => ['sometimes', 'boolean'],
        ];
    }
}
