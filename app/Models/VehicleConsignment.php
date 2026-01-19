<?php

namespace App\Models;

use App\Domain\Consignments\ConsignmentStatus;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VehicleConsignment extends Model
{
    use HasFactory;

    public $table = 'vehicle_consignments';

    public const STATUS_ACTIVE = ConsignmentStatus::ACTIVE;
    public const STATUS_CLOSED = ConsignmentStatus::CLOSED;

    public const STATUS_OPTIONS = [
        ConsignmentStatus::ACTIVE,
        ConsignmentStatus::CLOSED,
    ];

    protected $dates = [
        'starts_at',
        'ends_at',
        'created_at',
        'updated_at',
    ];

    protected $fillable = [
        'vehicle_id',
        'from_unit_id',
        'to_unit_id',
        'reference_value',
        'starts_at',
        'ends_at',
        'status',
    ];

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }

    public function from_unit()
    {
        return $this->belongsTo(OperationalUnit::class, 'from_unit_id');
    }

    public function to_unit()
    {
        return $this->belongsTo(OperationalUnit::class, 'to_unit_id');
    }
}
