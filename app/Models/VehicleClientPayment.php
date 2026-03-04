<?php

namespace App\Models;

use App\Traits\Auditable;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VehicleClientPayment extends Model
{
    use SoftDeletes, Auditable, HasFactory;

    public $table = 'vehicle_client_payments';

    protected $dates = [
        'paid_at',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $fillable = [
        'vehicle_id',
        'payment_method_id',
        'paid_at',
        'amount',
    ];

    protected function serializeDate(DateTimeInterface $date): string
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }

    public function payment_method()
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id');
    }

    public function getPaidAtAttribute($value)
    {
        return $value ? Carbon::parse($value)->format(config('panel.date_format')) : null;
    }

    public function setPaidAtAttribute($value): void
    {
        $this->attributes['paid_at'] = $value
            ? Carbon::createFromFormat(config('panel.date_format'), $value)->format('Y-m-d')
            : null;
    }
}
