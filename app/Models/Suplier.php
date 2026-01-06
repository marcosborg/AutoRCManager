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
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class, 'suplier_id');
    }

    public function supplier_orders(): HasMany
    {
        return $this->hasMany(SupplierOrder::class, 'suplier_id');
    }

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }
}
