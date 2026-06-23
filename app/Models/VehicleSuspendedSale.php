<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VehicleSuspendedSale extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_CONVERTED = 'converted';

    public $table = 'vehicle_suspended_sales';

    protected $fillable = [
        'vehicle_id',
        'client_id',
        'previous_general_state_id',
        'status',
        'suspended_at',
        'cancelled_at',
        'converted_at',
        'suspended_by_id',
        'cancelled_by_id',
        'converted_by_id',
        'notes',
    ];

    protected $casts = [
        'suspended_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'converted_at' => 'datetime',
    ];

    protected function serializeDate(DateTimeInterface $date): string
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function previous_general_state()
    {
        return $this->belongsTo(GeneralState::class, 'previous_general_state_id');
    }

    public function suspended_by()
    {
        return $this->belongsTo(User::class, 'suspended_by_id');
    }

    public function cancelled_by()
    {
        return $this->belongsTo(User::class, 'cancelled_by_id');
    }

    public function converted_by()
    {
        return $this->belongsTo(User::class, 'converted_by_id');
    }
}
