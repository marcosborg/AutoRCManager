<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkshopIntervention extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_SELECT = [
        'planned' => 'Planeado',
        'in_progress' => 'Em curso',
        'completed' => 'Concluído',
        'cancelled' => 'Cancelado',
    ];

    protected $fillable = [
        'repair_id', 'type_id', 'title', 'description', 'planned_start_date',
        'planned_end_date', 'status', 'created_by_id', 'completed_by_id', 'completed_at',
    ];

    protected $casts = [
        'planned_start_date' => 'date',
        'planned_end_date' => 'date',
        'completed_at' => 'datetime',
    ];

    public function repair()
    {
        return $this->belongsTo(Repair::class);
    }

    public function type()
    {
        return $this->belongsTo(WorkshopInterventionType::class, 'type_id');
    }

    public function mechanics()
    {
        return $this->belongsToMany(User::class, 'workshop_intervention_user')->withTimestamps();
    }

    public function workLogs()
    {
        return $this->hasMany(RepairWorkLog::class, 'workshop_intervention_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function completedBy()
    {
        return $this->belongsTo(User::class, 'completed_by_id');
    }
}
