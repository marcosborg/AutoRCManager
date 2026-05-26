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
