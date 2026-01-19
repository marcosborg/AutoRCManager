<?php

namespace App\Models;

use App\Domain\Ownership\OwnershipType;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VehicleOwnership extends Model
{
    use HasFactory;

    public $table = 'vehicle_ownerships';

    public const OWNER_TYPE_GROUP = OwnershipType::GROUP;
    public const OWNER_TYPE_EXTERNAL = OwnershipType::EXTERNAL;

    public const OWNER_TYPE_OPTIONS = [
        OwnershipType::GROUP,
        OwnershipType::EXTERNAL,
    ];

    protected $dates = [
        'starts_at',
        'ends_at',
        'created_at',
        'updated_at',
    ];

    protected $fillable = [
        'vehicle_id',
        'owner_type',
        'client_id',
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

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }
}
