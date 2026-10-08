<?php

namespace App\Models;

use App\Traits\Auditable;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PartOrderItem extends Model
{
    use SoftDeletes, HasFactory, Auditable;

    public const STATUS_SELECT = [
        'pending' => 'Pendente',
        'ordered' => 'Encomendado',
        'shipped' => 'Enviado',
        'received' => 'Recebido',
        'partially_received' => 'Parcialmente recebido',
        'installed' => 'Instalado',
        'returned' => 'Devolvido',
    ];

    protected $fillable = [
        'part_order_id',
        'reference',
        'description',
        'quantity',
        'unit_price_estimated',
        'unit_price_final',
        'iva_percentage',
        'total_estimated',
        'total_final',
        'status',
        'is_correct_part',
        'observations',
    ];

    public function receivedAmount(): float
    {
        return $this->received_quantity !== null ? (float) $this->received_quantity
            : (in_array($this->status, ['received', 'installed'], true) ? (float) $this->quantity : 0);
    }

    protected static function booted(): void
    {
        static::saving(function (self $item) {
            if ($item->received_quantity === null) {
                return;
            }
            if ((float) $item->quantity < (float) $item->received_quantity) {
                throw \Illuminate\Validation\ValidationException::withMessages(['items' => 'A quantidade encomendada não pode ser inferior à quantidade recebida.']);
            }
            if (! in_array($item->status, ['installed', 'returned'], true)) {
                $item->status = (float) $item->received_quantity >= (float) $item->quantity ? 'received'
                    : ((float) $item->received_quantity > 0 ? 'partially_received' : 'ordered');
            } elseif ($item->status === 'installed' && (float) $item->received_quantity < (float) $item->quantity) {
                throw \Illuminate\Validation\ValidationException::withMessages(['items' => 'Conclua a receção antes de marcar a linha como instalada.']);
            }
        });
        static::deleting(function (self $item) {
            if ($item->received_quantity !== null) {
                throw \Illuminate\Validation\ValidationException::withMessages(['items' => 'Uma peça com histórico de receção não pode ser eliminada.']);
            }
        });
    }

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function part_order()
    {
        return $this->belongsTo(PartOrder::class, 'part_order_id');
    }

    public function quotes()
    {
        return $this->hasMany(PartQuote::class, 'part_order_item_id');
    }

    public function calculateTotals(): void
    {
        $quantity = (float) ($this->quantity ?? 0);
        $ivaMultiplier = 1 + ((float) ($this->iva_percentage ?? 0) / 100);
        $this->total_estimated = $this->unit_price_estimated !== null ? $quantity * (float) $this->unit_price_estimated * $ivaMultiplier : null;
        $this->total_final = $this->unit_price_final !== null ? $quantity * (float) $this->unit_price_final * $ivaMultiplier : null;
    }
}
