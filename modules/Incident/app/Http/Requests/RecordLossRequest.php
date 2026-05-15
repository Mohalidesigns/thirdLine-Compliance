<?php

declare(strict_types=1);

namespace Modules\Incident\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RecordLossRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'basel_category' => ['required', 'string', 'in:internal_fraud,external_fraud,employment_practices,clients_products_business,damage_physical_assets,business_disruption,execution_delivery_process,other'],
            'gross_loss' => ['required', 'numeric', 'min:0'],
            'recovery_amount' => ['nullable', 'numeric', 'min:0'],
            'net_loss_currency' => ['nullable', 'string', 'size:3'],
            'event_date' => ['required', 'date'],
            'recognized_date' => ['required', 'date'],
        ];
    }
}
