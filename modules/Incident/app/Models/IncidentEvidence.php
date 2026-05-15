<?php

declare(strict_types=1);

namespace Modules\Incident\Models;

use App\Concerns\BelongsToTenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IncidentEvidence extends Model
{
    use BelongsToTenant;

    // Evidence is immutable once created — no updated_at
    public $timestamps = false;

    protected $fillable = [
        'tenant_id',
        'incident_id',
        'type',
        'title',
        'description',
        'file_path',
        'uploaded_by',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function incident(): BelongsTo
    {
        return $this->belongsTo(Incident::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            'document' => 'Document',
            'screenshot' => 'Screenshot',
            'email' => 'Email',
            'log' => 'Log',
            'witness_statement' => 'Witness Statement',
            'other' => 'Other',
            default => ucfirst($this->type),
        };
    }
}
