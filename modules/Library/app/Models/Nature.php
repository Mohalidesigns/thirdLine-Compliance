<?php

declare(strict_types=1);

namespace Modules\Library\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Nature extends Model
{
    protected $fillable = ['name'];

    public function instruments(): HasMany
    {
        return $this->hasMany(Instrument::class);
    }
}
