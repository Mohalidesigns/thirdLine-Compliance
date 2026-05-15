<?php

declare(strict_types=1);

namespace Modules\Controls\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RecordTestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'period_start' => ['nullable', 'date'],
            'period_end' => ['nullable', 'date', 'after_or_equal:period_start'],
            'sample_size' => ['nullable', 'integer', 'min:1'],
            'population_size' => ['nullable', 'integer', 'min:1'],
            'confidence_level' => ['nullable', 'numeric', 'min:0.01', 'max:1'],
            'outcome' => ['required', 'string', 'in:passed,partial,failed,not_applicable'],
            'evidence_url' => ['nullable', 'url', 'max:500'],
            'findings' => ['nullable', 'string'],
            'tested_at' => ['nullable', 'date'],
        ];
    }
}
