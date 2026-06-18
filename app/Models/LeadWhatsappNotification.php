<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeadWhatsappNotification extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_SENT = 'sent';
    public const STATUS_FAILED = 'failed';

    public $table = 'lead_whatsapp_notifications';

    protected $fillable = [
        'lead_id',
        'user_id',
        'access_token_id',
        'phone',
        'message',
        'status',
        'external_id',
        'metadata',
        'sent_at',
        'failed_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'sent_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    protected function serializeDate(DateTimeInterface $date): string
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function access_token()
    {
        return $this->belongsTo(LeadAccessToken::class, 'access_token_id');
    }
}
