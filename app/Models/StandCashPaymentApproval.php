<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StandCashPaymentApproval extends Model
{
    use SoftDeletes, HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    public const STATUS_SELECT = [
        self::STATUS_PENDING => 'Pendente',
        self::STATUS_APPROVED => 'Validado',
        self::STATUS_REJECTED => 'Rejeitado',
    ];

    public $table = 'stand_cash_payment_approvals';

    protected $fillable = [
        'vehicle_client_payment_id',
        'vehicle_id',
        'created_by_id',
        'approved_by_id',
        'cash_operation_id',
        'status',
        'approved_at',
        'rejected_at',
        'rejection_reason',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public function payment()
    {
        return $this->belongsTo(VehicleClientPayment::class, 'vehicle_client_payment_id');
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }

    public function created_by()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function approved_by()
    {
        return $this->belongsTo(User::class, 'approved_by_id');
    }

    public function cash_operation()
    {
        return $this->belongsTo(AccountOperation::class, 'cash_operation_id');
    }
}
