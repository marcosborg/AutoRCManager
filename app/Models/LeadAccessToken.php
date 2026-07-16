<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeadAccessToken extends Model
{
    use HasFactory;

    public $table = 'lead_access_tokens';

    protected $fillable = [
        'lead_id',
        'user_id',
        'assignment_history_id',
        'token_hash',
        'expires_at',
        'first_open_deadline_at',
        'last_used_at',
        'revoked_at',
        'revoked_reason',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'first_open_deadline_at' => 'datetime',
        'last_used_at' => 'datetime',
        'revoked_at' => 'datetime',
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

    public function assignment_history()
    {
        return $this->belongsTo(LeadAssignmentHistory::class);
    }

    public function contact_events()
    {
        return $this->hasMany(LeadContactEvent::class, 'access_token_id');
    }

    public function isUsable(): bool
    {
        if ($this->revoked_at !== null || ! $this->expires_at?->isFuture()) {
            return false;
        }

        return $this->last_used_at !== null || ! $this->firstOpenDeadlinePassed();
    }

    public function firstOpenDeadlinePassed(): bool
    {
        return $this->last_used_at === null
            && $this->first_open_deadline_at !== null
            && $this->first_open_deadline_at->isPast();
    }
}
