<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Suplier extends Model
{
    use SoftDeletes, HasFactory;

    public $table = 'supliers';

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $fillable = [
        'name',
        'email',
        'phone',
        'mobile',
        'address',
        'nif',
        'average_delivery_days',
        'active',
        'notes',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class, 'suplier_id');
    }

    public function part_orders(): HasMany
    {
        return $this->hasMany(PartOrder::class, 'suplier_id');
    }

    public function part_payments(): HasMany
    {
        return $this->hasMany(PartPayment::class, 'suplier_id');
    }

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }
}
