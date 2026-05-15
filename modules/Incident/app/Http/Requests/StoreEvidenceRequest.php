<?php

declare(strict_types=1);

namespace Modules\Incident\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEvidenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'in:document,screenshot,email,log,witness_statement,other'],
            'title' => ['required', 'string', 'max:500'],
            'description' => ['nullable', 'string'],
            'file_path' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
