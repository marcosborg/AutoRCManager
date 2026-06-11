<?php

namespace App\Models;

use App\Traits\Auditable;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class ExternalService extends Model implements HasMedia
{
    use Auditable, HasFactory, InteractsWithMedia, SoftDeletes;

    public const PRIORITY_SELECT = [
        'low' => 'Baixa',
        'normal' => 'Normal',
        'urgent' => 'Urgente',
    ];

    public const STATUS_SELECT = [
        'requested' => 'Pedido',
        'scheduled' => 'Agendado',
        'in_progress' => 'Em curso',
        'completed' => 'Concluído',
        'cancelled' => 'Cancelado',
    ];

    protected $fillable = [
        'vehicle_id', 'suplier_id', 'requested_by_id', 'description', 'priority', 'status',
        'requested_delivery_days', 'expected_date', 'completed_date', 'amount', 'notes',
    ];

    protected $dates = ['expected_date', 'completed_date', 'created_at', 'updated_at', 'deleted_at'];

    protected $casts = ['amount' => 'float'];

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }

    public function suplier()
    {
        return $this->belongsTo(Suplier::class, 'suplier_id');
    }

    public function requested_by()
    {
        return $this->belongsTo(User::class, 'requested_by_id');
    }

    public function getInvoiceFileAttribute()
    {
        return $this->getMedia('invoice_file');
    }
}
