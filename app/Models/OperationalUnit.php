<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OperationalUnit extends Model
{
    use HasFactory;

    public $table = 'operational_units';

    protected $dates = [
        'created_at',
        'updated_at',
    ];

    protected $fillable = [
        'name',
        'code',
        'is_internal',
    ];

    protected $casts = [
        'is_internal' => 'boolean',
    ];

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function vehicle_locations()
    {
        return $this->hasMany(VehicleLocation::class, 'operational_unit_id');
    }

    public function consignments_from()
    {
        return $this->hasMany(VehicleConsignment::class, 'from_unit_id');
    }

    public function consignments_to()
    {
        return $this->hasMany(VehicleConsignment::class, 'to_unit_id');
    }
}
