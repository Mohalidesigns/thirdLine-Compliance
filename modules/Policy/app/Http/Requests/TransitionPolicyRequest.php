<?php

declare(strict_types=1);

namespace Modules\Policy\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TransitionPolicyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'to' => ['required', 'string', 'in:draft,in_review,approved,published,in_force,under_review,superseded'],
            'note' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
