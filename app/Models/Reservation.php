<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reservation extends Model
{
    protected $fillable = [
        'unit_id',
        'guest_name',
        'guest_phone',
        'check_in',
        'check_out',
        'status',
        'total_amount'
    ];

    // Una reserva pertenece a una unidad
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }
}
