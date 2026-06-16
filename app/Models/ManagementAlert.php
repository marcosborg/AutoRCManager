<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ManagementAlert extends Model
{
    use HasFactory;

    public const TYPE_APPROVAL_LOT = 'approval_lot';
    public const TYPE_APPROVAL_PAYMENT = 'approval_payment';
    public const TYPE_STOCK_AVAILABLE = 'stock_available';
    public const TYPE_VEHICLE_SOLD = 'vehicle_sold';
    public const TYPE_TRADE_IN_RECEIVED = 'trade_in_received';
    public const TYPE_CONSIGNMENT_CREATED = 'consignment_created';

    protected $fillable = [
        'type',
        'dedupe_key',
        'title',
        'message',
        'subject_type',
        'subject_id',
        'event_at',
        'read_at',
        'read_by_id',
    ];

    protected $casts = [
        'event_at' => 'datetime',
        'read_at' => 'datetime',
    ];

    public function subject()
    {
        return $this->morphTo();
    }

    public function read_by()
    {
        return $this->belongsTo(User::class, 'read_by_id');
    }
}
