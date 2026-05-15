<?php

declare(strict_types=1);

namespace Modules\Rcsa\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRiskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'cycle_id' => ['required', 'integer', 'exists:risk_assessment_cycles,id'],
            'title' => ['required', 'string', 'max:300'],
            'description' => ['required', 'string'],
            'category' => ['required', 'string', 'in:operational,credit,market,liquidity,compliance,reputational,strategic,cyber,aml,conduct,other'],
            'risk_owner' => ['nullable', 'string', 'max:200'],
            'inherent_likelihood' => ['nullable', 'integer', 'min:1', 'max:5'],
            'inherent_impact' => ['nullable', 'integer', 'min:1', 'max:5'],
            'residual_likelihood' => ['nullable', 'integer', 'min:1', 'max:5'],
            'residual_impact' => ['nullable', 'integer', 'min:1', 'max:5'],
            'linked_obligation_ids' => ['nullable', 'array'],
            'linked_obligation_ids.*' => ['integer'],
            'mitigation_summary' => ['nullable', 'string'],
            'accept_basis' => ['nullable', 'string'],
        ];
    }
}
