<?php

declare(strict_types=1);

namespace Modules\Policy\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PolicyVersion extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'policy_id',
        'version',
        'state',
        'body_snapshot',
        'published_pdf_path',
        'transitioned_by',
        'transitioned_at',
        'transition_note',
    ];

    protected $casts = [
        'version' => 'integer',
        'transitioned_at' => 'datetime',
    ];

    public function policy(): BelongsTo
    {
        return $this->belongsTo(Policy::class);
    }

    public function transitionedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'transitioned_by');
    }
}
