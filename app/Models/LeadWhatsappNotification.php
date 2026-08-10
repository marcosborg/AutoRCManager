<?php

namespace App\Models;

use App\Jobs\SendWhatsappLeadNotification;
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
        'delivery_key',
        'metadata',
        'sent_at',
        'scheduled_for',
        'attempted_at',
        'attempts',
        'failed_at',
        'provider_status_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'sent_at' => 'datetime',
        'scheduled_for' => 'datetime',
        'attempted_at' => 'datetime',
        'failed_at' => 'datetime',
        'provider_status_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::created(function (LeadWhatsappNotification $notification): void {
            if (config('whatsapp.transport') === 'cloud'
                && $notification->status === self::STATUS_PENDING
                && $notification->phone) {
                SendWhatsappLeadNotification::dispatch($notification->id)->afterCommit();
            }
        });
    }

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
