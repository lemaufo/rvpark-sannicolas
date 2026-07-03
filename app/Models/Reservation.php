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
        'guest_email',
        'nationality',
        'license_plate',
        'check_in',
        'check_in_time',
        'check_out',
        'check_out_time',
        'status',
        'total_amount',
        'cancel_reason'
    ];

    // Una reserva pertenece a una unidad
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    // Una reserva tiene muchas imágenes
    public function images()
    {
        return $this->hasMany(ReservationImage::class);
    }
}
