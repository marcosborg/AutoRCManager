<?php

namespace App\Models;

use App\Jobs\SendWhatsappChatMessage;
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
        'provider_status_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'sent_at' => 'datetime',
        'provider_status_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::created(function (ChatMessage $message): void {
            if (config('whatsapp.transport') === 'cloud'
                && $message->sender === 'assistant'
                && $message->delivery_status === 'pending') {
                SendWhatsappChatMessage::dispatch($message->id)->afterCommit();
            }
        });
    }

    protected function serializeDate(DateTimeInterface $date): string
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function conversation()
    {
        return $this->belongsTo(ChatConversation::class, 'conversation_id');
    }
}
