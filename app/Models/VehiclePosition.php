<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VehiclePosition extends Model
{
    use HasFactory;

    /**
     * Tabela associada ao modelo.
     */
    protected $table = 'vehicle_positions';

    /**
     * Atributos em massa permitidos.
     */
    protected $fillable = [
        'tracker_id',
        'latitude',
        'longitude',
        'speed_kph',
        'fix_valid',
        'voltage',
        'reported_at',
        'raw_data',
    ];

    /**
     * Casts de atributos.
     */
    protected $casts = [
        'latitude'    => 'float',
        'longitude'   => 'float',
        'speed_kph'   => 'integer',
        'fix_valid'   => 'boolean',
        'voltage'     => 'float',
        'reported_at' => 'datetime',
    ];
}
