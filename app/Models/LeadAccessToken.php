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
        'token_hash',
        'expires_at',
        'last_used_at',
        'revoked_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
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

    public function isUsable(): bool
    {
        return $this->revoked_at === null && $this->expires_at?->isFuture();
    }
}
