<?php

namespace App\Models;

use App\Traits\Auditable;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PartQuote extends Model
{
    use SoftDeletes, HasFactory, Auditable;

    protected $fillable = [
        'part_order_item_id',
        'suplier_id',
        'quoted_price',
        'estimated_delivery_days',
        'notes',
        'selected',
        'requested_at',
        'responded_at',
    ];

    protected $casts = [
        'selected' => 'boolean',
    ];

    protected $dates = [
        'requested_at',
        'responded_at',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function item()
    {
        return $this->belongsTo(PartOrderItem::class, 'part_order_item_id');
    }

    public function suplier()
    {
        return $this->belongsTo(Suplier::class, 'suplier_id');
    }
}
