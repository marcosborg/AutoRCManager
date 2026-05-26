<?php

namespace App\Models;

use App\Traits\Auditable;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PartPayment extends Model
{
    use SoftDeletes, HasFactory, Auditable;

    public const METHOD_SELECT = [
        'cash' => 'Caixa',
        'bank_transfer' => 'Transferencia bancaria',
        'credit_card' => 'Cartao de credito',
        'mbway' => 'MB Way',
        'current_account' => 'Conta corrente',
        'other' => 'Outro',
    ];

    public const CONDITION_SELECT = [
        'immediate' => 'Imediato',
        '30_days' => '30 dias',
        '60_days' => '60 dias',
        '90_days' => '90 dias',
    ];

    public const STATUS_SELECT = [
        'pending' => 'Pendente',
        'partially_paid' => 'Parcialmente pago',
        'paid' => 'Pago',
        'overdue' => 'Vencido',
        'cancelled' => 'Cancelado',
    ];

    protected $fillable = [
        'part_order_id',
        'suplier_id',
        'payment_method',
        'payment_condition',
        'amount',
        'payment_date',
        'due_date',
        'reference',
        'paid_by_id',
        'payment_status',
        'notes',
    ];

    protected $dates = [
        'payment_date',
        'due_date',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function part_order()
    {
        return $this->belongsTo(PartOrder::class, 'part_order_id');
    }

    public function suplier()
    {
        return $this->belongsTo(Suplier::class, 'suplier_id');
    }

    public function paid_by()
    {
        return $this->belongsTo(User::class, 'paid_by_id');
    }
}
