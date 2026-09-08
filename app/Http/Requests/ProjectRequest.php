<?php

namespace App\Http\Requests;

use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;

class ProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        $project = $this->route('project');

        return $project ? $this->user()->can('update', $project) : $this->user()->can('create', [Project::class, $this->route('organization')]);
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:150'],
            'description' => ['nullable', 'string', 'max:5000'],
            'base_url' => ['nullable', 'url:http,https', 'max:2048'],
            'check_status_url' => ['nullable', 'url:http,https', 'max:2048'],
            'active' => ['sometimes', 'boolean'],
            'check_interval_seconds' => ['sometimes', 'integer', 'min:30', 'max:86400'],
            'timeout_seconds' => ['sometimes', 'integer', 'min:1', 'max:30'],
            'failure_threshold' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'latency_threshold_ms' => ['sometimes', 'integer', 'min:1', 'max:60000'],
        ];
    }
}
