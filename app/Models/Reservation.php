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
        'check_in_time',
        'check_out',
        'check_out_time',
        'status',
        'total_amount'
    ];

    // Una reserva pertenece a una unidad
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }
}
