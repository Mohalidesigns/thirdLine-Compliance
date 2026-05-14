<?php

declare(strict_types=1);

namespace Modules\Library\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreObligationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'instrument_id' => ['required', 'integer', 'exists:instruments,id'],
            'reference' => ['nullable', 'string', 'max:50'],
            'title' => ['required', 'string', 'max:500'],
            'description' => ['required', 'string'],
            'due_basis' => ['required', 'in:recurring,one_off,event_driven'],
            'frequency' => ['nullable', 'in:daily,weekly,monthly,quarterly,semiannual,annual,adhoc'],
            'next_due_date' => ['nullable', 'date'],
            'responsible_team' => ['nullable', 'string', 'max:200'],
            'status' => ['required', 'in:open,in_progress,satisfied,overdue'],
        ];
    }
}
