<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OperationalStatus extends Model
{
    // Usamos 'false' porque nosotros manejaremos la fecha con changed_at o timestamps
    protected $fillable = [
        'unit_id',
        'status',
        'user_id',
        'changed_at'
    ];

    // El cambio pertenece a una unidad
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    // El cambio fue hecho por un usuario
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
