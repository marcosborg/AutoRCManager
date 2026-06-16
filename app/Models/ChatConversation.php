<?php

namespace App\Models;

use App\Traits\Auditable;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChatConversation extends Model
{
    use SoftDeletes, HasFactory, Auditable;

    public const STATUS_SELECT = [
        'active' => 'Ativa',
        'waiting_human' => 'A aguardar humano',
        'closed' => 'Fechada',
    ];

    public $table = 'chat_conversations';

    protected $fillable = [
        'assistant_id',
        'lead_id',
        'channel_id',
        'external_id',
        'customer_identifier',
        'customer_phone',
        'status',
        'human_takeover',
        'last_message_at',
    ];

    protected $casts = [
        'human_takeover' => 'boolean',
        'last_message_at' => 'datetime',
    ];

    protected function serializeDate(DateTimeInterface $date): string
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function assistant()
    {
        return $this->belongsTo(AiAssistant::class, 'assistant_id');
    }

    public function lead()
    {
        return $this->belongsTo(ChatLead::class, 'lead_id');
    }

    public function channel()
    {
        return $this->belongsTo(ChatChannel::class, 'channel_id');
    }

    public function messages()
    {
        return $this->hasMany(ChatMessage::class, 'conversation_id');
    }
}
