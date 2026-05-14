<?php

declare(strict_types=1);

namespace Modules\Library\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInstrumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'source_title' => ['required', 'string', 'max:500'],
            'objectives' => ['nullable', 'string'],
            'date_issue' => ['nullable', 'date'],
            'date_commence' => ['nullable', 'date'],
            'date_repeal' => ['nullable', 'date', 'after_or_equal:date_commence'],
            'regulator_id' => ['required', 'integer', 'exists:regulators,id'],
            'instrument_type_id' => ['required', 'integer', 'exists:instrument_types,id'],
            'nature_id' => ['required', 'integer', 'exists:natures,id'],
            'status_id' => ['required', 'integer', 'exists:statuses,id'],
            'area_of_focus_id' => ['required', 'integer', 'exists:areas_of_focus,id'],
            'risk_rating_id' => ['required', 'integer', 'exists:risk_ratings,id'],
            'risk_rating_explain' => ['nullable', 'string'],
            'commercial_bank_relevance' => ['nullable', 'string'],
            'commercial_bank_compliance_context' => ['nullable', 'string'],
            'applicability' => ['required', 'in:Yes,No,Partially'],
            'link_url' => ['nullable', 'url', 'max:2048'],
            'parent_id' => ['nullable', 'integer', 'exists:instruments,id'],
        ];
    }
}
