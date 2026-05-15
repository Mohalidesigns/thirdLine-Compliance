<?php

declare(strict_types=1);

namespace Modules\Incident\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TransitionIncidentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'to' => ['required', 'string', 'in:triaged,investigating,remediation,resolved,closed'],
        ];
    }
}
