<?php

declare(strict_types=1);

namespace Modules\Policy\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePolicyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:500'],
            'category' => ['required', 'string', 'in:aml,data_protection,risk,conduct,cyber,governance,other'],
            'owner_team' => ['required', 'string', 'max:200'],
            'summary' => ['nullable', 'string'],
            'body' => ['nullable', 'string'],
            'effective_date' => ['nullable', 'date'],
            'next_review_date' => ['nullable', 'date', 'after_or_equal:effective_date'],
        ];
    }
}
