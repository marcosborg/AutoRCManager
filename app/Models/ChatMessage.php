<?php

namespace App\Models;

use App\Traits\Auditable;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChatMessage extends Model
{
    use SoftDeletes, HasFactory, Auditable;

    public const SENDER_SELECT = [
        'customer' => 'Cliente',
        'assistant' => 'Assistente',
        'human' => 'Humano',
        'system' => 'Sistema',
    ];

    public const DELIVERY_STATUS_SELECT = [
        'pending' => 'Pendente',
        'sent' => 'Enviada',
        'delivered' => 'Entregue',
        'read' => 'Lida',
        'failed' => 'Falhou',
    ];

    public $table = 'chat_messages';

    protected $fillable = [
        'conversation_id',
        'sender',
        'message',
        'external_id',
        'delivery_status',
        'metadata',
        'sent_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'sent_at' => 'datetime',
    ];

    protected function serializeDate(DateTimeInterface $date): string
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function conversation()
    {
        return $this->belongsTo(ChatConversation::class, 'conversation_id');
    }
}
