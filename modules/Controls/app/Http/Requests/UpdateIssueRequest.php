<?php

declare(strict_types=1);

namespace Modules\Controls\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateIssueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'status' => ['sometimes', 'required', 'string', 'in:open,in_progress,resolved,closed,dismissed'],
            'resolution_notes' => ['nullable', 'string'],
            'due_date' => ['nullable', 'date'],
            'owner_team' => ['nullable', 'string', 'max:200'],
        ];
    }
}
