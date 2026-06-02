<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SaleClosureApproval extends Model
{
    use SoftDeletes, HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    public const TRIGGER_PAYMENT = 'payment';
    public const TRIGGER_TRADE_IN = 'trade_in';

    public const STATUS_SELECT = [
        self::STATUS_PENDING => 'Pendente',
        self::STATUS_APPROVED => 'Validado',
        self::STATUS_REJECTED => 'Rejeitado',
    ];

    public const TRIGGER_SELECT = [
        self::TRIGGER_PAYMENT => 'Pagamento',
        self::TRIGGER_TRADE_IN => 'Retoma',
    ];

    public $table = 'sale_closure_approvals';

    protected $fillable = [
        'vehicle_id',
        'closed_by_id',
        'approved_by_id',
        'trigger_type',
        'trigger_id',
        'status',
        'sales_total',
        'client_payments_total',
        'trade_ins_total',
        'outstanding_amount',
        'closed_at',
        'approved_at',
        'rejected_at',
        'rejection_reason',
    ];

    protected $casts = [
        'sales_total' => 'float',
        'client_payments_total' => 'float',
        'trade_ins_total' => 'float',
        'outstanding_amount' => 'float',
        'closed_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }

    public function closed_by()
    {
        return $this->belongsTo(User::class, 'closed_by_id');
    }

    public function approved_by()
    {
        return $this->belongsTo(User::class, 'approved_by_id');
    }
}
