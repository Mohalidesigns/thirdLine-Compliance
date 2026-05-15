<?php

declare(strict_types=1);

namespace Modules\Incident\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateIncidentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:500'],
            'description' => ['sometimes', 'string'],
            'category' => ['sometimes', 'string', 'in:cyber,data_breach,conduct,financial_crime,operational,customer_protection,other'],
            'severity' => ['sometimes', 'string', 'in:critical,high,medium,low'],
            'basel_category' => ['nullable', 'string', 'in:internal_fraud,external_fraud,employment_practices,clients_products_business,damage_physical_assets,business_disruption,execution_delivery_process,other'],
            'occurred_at' => ['nullable', 'date'],
            'detected_at' => ['sometimes', 'date'],
            'reporter_external_source' => ['nullable', 'string', 'max:200'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'financial_impact' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'is_data_breach' => ['boolean'],
            'is_cyber_incident' => ['boolean'],
            'affects_customers' => ['boolean'],
            'affected_customer_count' => ['nullable', 'integer', 'min:0'],
            'root_cause' => ['nullable', 'string'],
            'lessons_learned' => ['nullable', 'string'],
            'linked_risk_id' => ['nullable', 'integer'],
            'linked_control_id' => ['nullable', 'integer'],
            'linked_policy_id' => ['nullable', 'integer'],
        ];
    }
}
