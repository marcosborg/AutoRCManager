<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VehiclePurchasePriceHistory extends Model
{
    use HasFactory;

    public $table = 'vehicle_purchase_price_histories';

    protected $fillable = [
        'vehicle_id',
        'client_id',
        'changed_by_id',
        'previous_purchase_price',
        'new_purchase_price',
        'sale_price',
        'reason',
    ];

    protected $casts = [
        'previous_purchase_price' => 'float',
        'new_purchase_price' => 'float',
        'sale_price' => 'float',
    ];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function changed_by()
    {
        return $this->belongsTo(User::class, 'changed_by_id');
    }
}
