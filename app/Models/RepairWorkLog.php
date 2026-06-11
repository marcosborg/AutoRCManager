<?php

namespace App\Models;

use App\Traits\Auditable;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RepairWorkLog extends Model
{
    use Auditable, HasFactory, SoftDeletes;

    public $table = 'repair_work_logs';

    protected $dates = [
        'started_at',
        'finished_at',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $fillable = [
        'repair_id',
        'workshop_intervention_id',
        'user_id',
        'started_at',
        'finished_at',
        'duration_minutes',
    ];

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function repair()
    {
        return $this->belongsTo(Repair::class, 'repair_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function workshopIntervention()
    {
        return $this->belongsTo(WorkshopIntervention::class, 'workshop_intervention_id');
    }
}
