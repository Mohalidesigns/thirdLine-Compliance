<?php

declare(strict_types=1);

namespace Modules\Controls\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateControlRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:300'],
            'description' => ['nullable', 'string'],
            'control_type' => ['sometimes', 'required', 'string', 'in:preventive,detective,corrective,compensating'],
            'nature' => ['sometimes', 'required', 'string', 'in:manual,automated,hybrid'],
            'frequency' => ['sometimes', 'required', 'string', 'in:continuous,daily,weekly,monthly,quarterly,semiannual,annual,event_driven'],
            'owner_team' => ['sometimes', 'required', 'string', 'max:200'],
            'linked_obligation_ids' => ['nullable', 'array'],
            'linked_obligation_ids.*' => ['integer'],
            'linked_risk_ids' => ['nullable', 'array'],
            'linked_risk_ids.*' => ['integer'],
            'status' => ['sometimes', 'string', 'in:active,deprecated,draft'],
            'next_test_due' => ['nullable', 'date'],
        ];
    }
}
