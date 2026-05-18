<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VehicleLotItem extends Model
{
    use SoftDeletes, Auditable, HasFactory;

    public $table = 'vehicle_lot_items';

    protected $fillable = [
        'vehicle_group_id',
        'vehicle_id',
        'original_price',
        'adjusted_price',
        'discount',
        'allocated_amount',
        'paid_amount',
        'invoiced_amount',
        'cash_amount',
        'status',
    ];

    protected $casts = [
        'original_price' => 'float',
        'adjusted_price' => 'float',
        'discount' => 'float',
        'allocated_amount' => 'float',
        'paid_amount' => 'float',
        'invoiced_amount' => 'float',
        'cash_amount' => 'float',
    ];

    public function lot()
    {
        return $this->belongsTo(VehicleGroup::class, 'vehicle_group_id');
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }

    public function getSaleTargetAttribute(): float
    {
        return (float) ($this->adjusted_price ?? $this->allocated_amount ?? $this->original_price ?? 0);
    }
}
