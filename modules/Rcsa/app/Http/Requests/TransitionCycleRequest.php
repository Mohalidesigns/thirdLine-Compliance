<?php

declare(strict_types=1);

namespace Modules\Rcsa\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TransitionCycleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'to' => ['required', 'string', 'in:planning,data_capture,scoring,in_review,signed_off,closed'],
            'workshop_note' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
