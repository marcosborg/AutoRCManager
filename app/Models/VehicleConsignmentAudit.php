<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VehicleConsignmentAudit extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'consignment_id',
        'action',
        'vehicle_id_before',
        'vehicle_id_after',
        'vehicle_license_before',
        'vehicle_license_after',
        'user_id',
        'user_name',
        'ip_address',
        'effective_starts_at',
        'effective_ends_at',
        'before',
        'after',
        'created_at',
    ];

    protected $casts = [
        'before' => 'array',
        'after' => 'array',
        'created_at' => 'datetime',
        'effective_starts_at' => 'datetime',
        'effective_ends_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
