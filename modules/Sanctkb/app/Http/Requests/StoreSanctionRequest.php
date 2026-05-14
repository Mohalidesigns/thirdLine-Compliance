<?php

declare(strict_types=1);

namespace Modules\Sanctkb\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSanctionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'regulator_id' => ['required', 'integer', 'exists:regulators,id'],
            'reference' => ['nullable', 'string', 'max:200'],
            'section' => ['nullable', 'string', 'max:200'],
            'offence' => ['required', 'string'],
            'party_name' => ['required', 'string', 'max:500'],
            'party_type' => ['required', 'in:individual,institution,committee'],
            'amount_naira' => ['nullable', 'numeric', 'min:0'],
            'penalty_type' => ['required', 'in:monetary,license_action,reprimand,other'],
            'effective_date' => ['required', 'date'],
            'source_url' => ['nullable', 'url', 'max:2048'],
        ];
    }
}
