<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    use SoftDeletes, HasFactory;

    public $table = 'clients';

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $fillable = [
        'name',
        'vat',
        'address',
        'location',
        'zip',
        'phone',
        'email',
        'country_id',
        'company_name',
        'company_vat',
        'company_address',
        'company_location',
        'company_zip',
        'company_phone',
        'company_email',
        'company_country_id',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function country()
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

    public function company_country()
    {
        return $this->belongsTo(Country::class, 'company_country_id');
    }

    public function vehicles()
    {
        return $this->hasMany(Vehicle::class, 'client_id', 'id');
    }

    public function vehicle_groups()
    {
        return $this->belongsToMany(VehicleGroup::class, 'client_vehicle_group');
    }

}
