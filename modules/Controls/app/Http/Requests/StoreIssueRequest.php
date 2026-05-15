<?php

declare(strict_types=1);

namespace Modules\Controls\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreIssueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:300'],
            'description' => ['nullable', 'string'],
            'severity' => ['required', 'string', 'in:low,medium,high,critical'],
            'linked_control_id' => ['nullable', 'integer', 'exists:controls,id'],
            'linked_obligation_id' => ['nullable', 'integer'],
            'linked_risk_id' => ['nullable', 'integer'],
            'due_at' => ['nullable', 'date'],
        ];
    }
}
