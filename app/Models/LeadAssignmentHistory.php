<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeadAssignmentHistory extends Model
{
    use HasFactory;

    public $table = 'lead_assignment_histories';

    protected $fillable = [
        'lead_id',
        'user_id',
        'assigned_by_id',
        'reason',
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

    public function assigned_by()
    {
        return $this->belongsTo(User::class, 'assigned_by_id');
    }

    public function access_tokens()
    {
        return $this->hasMany(LeadAccessToken::class, 'assignment_history_id');
    }

    public function contact_events()
    {
        return $this->hasMany(LeadContactEvent::class, 'assignment_history_id');
    }
}
