<?php

namespace App\Models;

use App\Traits\Auditable;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VehicleGroup extends Model
{
    use SoftDeletes, Auditable, HasFactory;

    public $table = 'vehicle_groups';

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $fillable = [
        'customer_id',
        'name',
        'type',
        'wholesale_pvp',
        'total_amount',
        'distribution_mode',
        'status',
        'approved_by',
        'approved_at',
        'notes',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $casts = [
        'wholesale_pvp' => 'float',
        'total_amount' => 'float',
        'approved_at' => 'datetime',
    ];

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function vehicles()
    {
        return $this->belongsToMany(Vehicle::class, 'vehicle_group_vehicle');
    }

    public function items()
    {
        return $this->hasMany(VehicleLotItem::class, 'vehicle_group_id');
    }

    public function clients()
    {
        return $this->belongsToMany(Client::class, 'client_vehicle_group');
    }

    public function customer()
    {
        return $this->belongsTo(Client::class, 'customer_id');
    }

    public function payments()
    {
        return $this->hasMany(LotPayment::class, 'vehicle_group_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function getEffectiveTotalAttribute(): float
    {
        $itemExtras = $this->relationLoaded('items')
            ? (float) $this->items->sum(fn (VehicleLotItem $item): float => (float) ($item->registration_amount ?? 0) + (float) ($item->tow_amount ?? 0))
            : (float) $this->items()->sum('registration_amount') + (float) $this->items()->sum('tow_amount');

        return (float) ($this->total_amount ?? $this->wholesale_pvp ?? 0) + $itemExtras;
    }

    public function getApprovedPaidTotalAttribute(): float
    {
        return (float) $this->payments()
            ->where('approval_status', LotPayment::STATUS_APPROVED)
            ->sum('amount');
    }

    public function getApprovedInvoicedTotalAttribute(): float
    {
        return (float) $this->payments()
            ->where('approval_status', LotPayment::STATUS_APPROVED)
            ->sum('invoiced_amount');
    }

    public function getApprovedCashTotalAttribute(): float
    {
        return (float) $this->payments()
            ->where('approval_status', LotPayment::STATUS_APPROVED)
            ->sum('cash_amount');
    }

    public function getApprovedBankTotalAttribute(): float
    {
        return (float) $this->payments()
            ->where('approval_status', LotPayment::STATUS_APPROVED)
            ->sum('bank_amount');
    }

    public function getApprovedCash2TotalAttribute(): float
    {
        return (float) $this->payments()
            ->where('approval_status', LotPayment::STATUS_APPROVED)
            ->sum('cash_2_amount');
    }
}
