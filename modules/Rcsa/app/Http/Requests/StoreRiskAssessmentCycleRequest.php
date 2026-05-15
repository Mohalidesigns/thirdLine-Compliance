<?php

declare(strict_types=1);

namespace Modules\Rcsa\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRiskAssessmentCycleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:200'],
            'lob' => ['required', 'string', 'in:Retail,Corporate,Treasury,Operations,IT,Compliance'],
            'cycle_year' => ['required', 'integer', 'min:2020', 'max:2099'],
            'cycle_quarter' => ['nullable', 'integer', 'min:1', 'max:4'],
            'methodology' => ['required', 'string', 'in:3x3,5x5'],
            'summary' => ['nullable', 'string'],
            'lead_assessor_id' => ['nullable', 'integer', 'exists:users,id'],
            'started_at' => ['nullable', 'date'],
            'sla_due_date' => ['nullable', 'date'],
        ];
    }
}
