<?php

declare(strict_types=1);

namespace Modules\Library\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Regulator extends Model
{
    protected $fillable = ['code', 'name', 'country', 'website_url'];

    public function instruments(): HasMany
    {
        return $this->hasMany(Instrument::class);
    }
}
