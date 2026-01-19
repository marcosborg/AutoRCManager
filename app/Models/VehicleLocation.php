<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VehicleLocation extends Model
{
    use HasFactory;

    public $table = 'vehicle_locations';

    protected $dates = [
        'starts_at',
        'ends_at',
        'created_at',
        'updated_at',
    ];

    protected $fillable = [
        'vehicle_id',
        'operational_unit_id',
        'starts_at',
        'ends_at',
    ];

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }

    public function operational_unit()
    {
        return $this->belongsTo(OperationalUnit::class, 'operational_unit_id');
    }
}
