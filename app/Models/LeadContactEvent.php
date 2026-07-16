<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeadContactEvent extends Model
{
    protected $fillable = [
        'lead_id', 'user_id', 'assignment_history_id', 'access_token_id', 'channel', 'clicked_at',
    ];

    protected $casts = ['clicked_at' => 'datetime'];

    public function lead() { return $this->belongsTo(Lead::class); }
    public function user() { return $this->belongsTo(User::class); }
    public function assignment_history() { return $this->belongsTo(LeadAssignmentHistory::class); }
    public function access_token() { return $this->belongsTo(LeadAccessToken::class); }
}
