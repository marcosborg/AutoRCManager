<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class VehicleStateTransfer extends Model
{
    use HasFactory;

    public $table = 'vehicle_state_transfers';

    protected $casts = [
        'snapshot' => 'array',
    ];

    protected $fillable = [
        'vehicle_id',
        'from_general_state_id',
        'to_general_state_id',
        'fuel_level',
        'snapshot',
        'user_id',
    ];

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }

    public function from_general_state()
    {
        return $this->belongsTo(GeneralState::class, 'from_general_state_id');
    }

    public function to_general_state()
    {
        return $this->belongsTo(GeneralState::class, 'to_general_state_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
