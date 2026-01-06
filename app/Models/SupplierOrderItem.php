<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupplierOrderItem extends Model
{
    use HasFactory;

    public $table = 'supplier_order_items';

    protected $dates = [
        'created_at',
        'updated_at',
    ];

    protected $fillable = [
        'supplier_order_id',
        'account_category_id',
        'item_name',
        'qty_ordered',
        'qty_received',
        'unit_price',
        'created_at',
        'updated_at',
    ];

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function supplier_order()
    {
        return $this->belongsTo(SupplierOrder::class, 'supplier_order_id');
    }

    public function account_category()
    {
        return $this->belongsTo(AccountCategory::class, 'account_category_id');
    }
}
