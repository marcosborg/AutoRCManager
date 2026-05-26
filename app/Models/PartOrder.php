<?php

namespace App\Models;

use App\Traits\Auditable;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PartOrder extends Model
{
    use SoftDeletes, HasFactory, Auditable;

    public const PRIORITY_SELECT = [
        'low' => 'Baixa',
        'normal' => 'Normal',
        'urgent' => 'Urgente',
    ];

    public const STATUS_SELECT = [
        'draft' => 'Rascunho',
        'requesting_quotes' => 'Pedido de cotacao',
        'ordered' => 'Encomendado',
        'partially_received' => 'Parcialmente recebido',
        'received' => 'Recebido',
        'delayed' => 'Atrasado',
        'cancelled' => 'Cancelado',
    ];

    protected $fillable = [
        'repair_id',
        'vehicle_id',
        'requested_by_id',
        'technician_id',
        'suplier_id',
        'priority',
        'status',
        'requested_delivery_days',
        'expected_delivery_date',
        'actual_delivery_date',
        'delay_alert_sent_at',
        'notes',
    ];

    protected $dates = [
        'expected_delivery_date',
        'actual_delivery_date',
        'delay_alert_sent_at',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function repair()
    {
        return $this->belongsTo(Repair::class, 'repair_id');
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }

    public function requested_by()
    {
        return $this->belongsTo(User::class, 'requested_by_id');
    }

    public function technician()
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

    public function suplier()
    {
        return $this->belongsTo(Suplier::class, 'suplier_id');
    }

    public function items()
    {
        return $this->hasMany(PartOrderItem::class, 'part_order_id');
    }

    public function payments()
    {
        return $this->hasMany(PartPayment::class, 'part_order_id');
    }

    public function receipts()
    {
        return $this->hasMany(PartReceipt::class, 'part_order_id');
    }

    public function refreshReceiptStatus(): void
    {
        $items = $this->items()->get();
        if ($items->isEmpty()) {
            return;
        }

        $receivedCount = $items->whereIn('status', ['received', 'installed'])->count();
        if ($receivedCount === $items->count()) {
            $this->update([
                'status' => 'received',
                'actual_delivery_date' => $this->actual_delivery_date ?: now()->toDateString(),
            ]);
            return;
        }

        if ($receivedCount > 0) {
            $this->update(['status' => 'partially_received']);
        }
    }
}
