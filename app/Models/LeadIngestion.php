<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeadIngestion extends Model
{
    public const STATUS_RECEIVED = 'received';
    public const STATUS_QUEUED = 'queued';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_PROCESSED = 'processed';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'source', 'external_id', 'lead_id', 'status', 'source_created_at',
        'received_at', 'processed_at', 'last_error', 'payload',
    ];

    protected $casts = [
        'source_created_at' => 'datetime',
        'received_at' => 'datetime',
        'processed_at' => 'datetime',
        'payload' => 'array',
    ];

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }
}
