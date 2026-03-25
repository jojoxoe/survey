<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Barangay extends Model
{
    protected $fillable = ['code', 'city_id', 'name'];

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }
}
