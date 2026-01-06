<?php

namespace App\Models;

use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class SupplierOrder extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    public $table = 'supplier_orders';

    protected $dates = [
        'order_date',
        'created_at',
        'updated_at',
    ];

    protected $fillable = [
        'suplier_id',
        'repair_id',
        'order_date',
        'notes',
        'invoice_total_confirmed',
        'parts_total_confirmed',
        'created_at',
        'updated_at',
    ];

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function suplier()
    {
        return $this->belongsTo(Suplier::class, 'suplier_id');
    }

    public function repair()
    {
        return $this->belongsTo(Repair::class, 'repair_id');
    }

    public function items()
    {
        return $this->hasMany(SupplierOrderItem::class, 'supplier_order_id');
    }

    public function getInvoiceAttachmentAttribute()
    {
        return $this->getMedia('invoice_attachment')->last();
    }

    public function getOrderDateAttribute($value)
    {
        return $value ? Carbon::parse($value)->format(config('panel.date_format')) : null;
    }

    public function setOrderDateAttribute($value)
    {
        $this->attributes['order_date'] = $value
            ? Carbon::createFromFormat(config('panel.date_format'), $value)->format('Y-m-d')
            : null;
    }
}
