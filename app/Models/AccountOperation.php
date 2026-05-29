<?php

namespace App\Models;

use App\Traits\Auditable;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AccountOperation extends Model
{
    use SoftDeletes, Auditable, HasFactory;

    public const TYPE_INCOME = 'income';
    public const TYPE_OUTCOME = 'outcome';

    public $table = 'account_operations';

    protected $fillable = [
        'description',
        'movement_type',
        'total',
        'account_item_id',
        'department_id',
        'cash_category_id',
        'vehicle_id',
        'qty',
        'date',
        'payment_method_id',
        'cash_box_id',
        'notes',
        'is_accounted',
        'accounted_at',
        'accounted_by',
        'transfer_group_id',
    ];

    protected $casts = [
        'total' => 'float',
        'is_accounted' => 'boolean',
        'accounted_at' => 'datetime',
    ];

    protected $dates = [
        'date',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function account_item()
    {
        return $this->belongsTo(AccountItem::class, 'account_item_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function cash_category()
    {
        return $this->belongsTo(CashCategory::class, 'cash_category_id');
    }

    public function cash_box()
    {
        return $this->belongsTo(CashBox::class, 'cash_box_id');
    }

    public function payment_method()
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id');
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }

    public function accountant()
    {
        return $this->belongsTo(User::class, 'accounted_by');
    }

    public function getDateAttribute($value)
    {
        return $value ? Carbon::parse($value)->format(config('panel.date_format')) : null;
    }

    public function setDateAttribute($value): void
    {
        $this->attributes['date'] = $value
            ? Carbon::parse($value)->format('Y-m-d')
            : null;
    }

    public function getDisplayDescriptionAttribute(): string
    {
        return $this->description ?: ($this->account_item->name ?? 'Movimento #' . $this->id);
    }

    public function getEffectiveTypeAttribute(): ?string
    {
        return $this->movement_type ?: ($this->account_item->type ?? null);
    }
}
