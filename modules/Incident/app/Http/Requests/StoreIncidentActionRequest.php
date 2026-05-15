<?php

declare(strict_types=1);

namespace Modules\Incident\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreIncidentActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('incidents.update') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'type' => 'required|in:corrective,preventive',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'owner_user_id' => 'nullable|integer|exists:users,id',
            'due_at' => 'required|date',
        ];
    }
}
