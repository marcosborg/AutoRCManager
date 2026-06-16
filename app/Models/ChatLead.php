<?php

namespace App\Models;

use App\Traits\Auditable;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChatLead extends Model
{
    use SoftDeletes, HasFactory, Auditable;

    public const PRIORITY_SELECT = ['low' => 'Baixa', 'medium' => 'Média', 'high' => 'Alta'];

    public const STATUS_SELECT = [
        'open' => 'Aberta',
        'qualified' => 'Qualificada',
        'waiting_human' => 'A aguardar humano',
        'sent_to_sales' => 'Enviada para comercial',
        'closed' => 'Fechada',
        'ignored' => 'Ignorada',
    ];

    public $table = 'chat_leads';

    protected $fillable = [
        'lead_id',
        'channel_id',
        'name',
        'phone',
        'email',
        'source',
        'external_id',
        'vehicle_reference',
        'vehicle_title',
        'vehicle_url',
        'budget_max',
        'monthly_payment',
        'wants_financing',
        'has_trade_in',
        'trade_in_brand',
        'trade_in_model',
        'trade_in_year',
        'trade_in_kms',
        'trade_in_notes',
        'urgency',
        'priority',
        'status',
        'summary',
        'ai_notes',
        'assigned_to',
    ];

    protected $casts = [
        'budget_max' => 'decimal:2',
        'monthly_payment' => 'decimal:2',
        'wants_financing' => 'boolean',
        'has_trade_in' => 'boolean',
    ];

    protected function serializeDate(DateTimeInterface $date): string
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function meta_lead()
    {
        return $this->belongsTo(Lead::class, 'lead_id');
    }

    public function channel()
    {
        return $this->belongsTo(ChatChannel::class, 'channel_id');
    }

    public function assigned_user()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function conversations()
    {
        return $this->hasMany(ChatConversation::class, 'lead_id');
    }
}
