<?php

declare(strict_types=1);

namespace Modules\Rcsa\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ScoreRiskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'in:inherent,residual'],
            'likelihood' => ['required', 'integer', 'min:1', 'max:5'],
            'impact' => ['required', 'integer', 'min:1', 'max:5'],
        ];
    }
}
