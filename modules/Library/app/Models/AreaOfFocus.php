<?php

declare(strict_types=1);

namespace Modules\Library\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AreaOfFocus extends Model
{
    protected $table = 'areas_of_focus';

    protected $fillable = ['name'];

    public function instruments(): HasMany
    {
        return $this->hasMany(Instrument::class);
    }
}
