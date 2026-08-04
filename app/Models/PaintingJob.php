<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaintingJob extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_OPEN = 'open';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_SELECT = ['open' => 'Aberta', 'completed' => 'Concluída'];

    public const INTENSITY_SELECT = ['light' => 'Leve', 'medium' => 'Médio', 'heavy' => 'Forte'];

    public const DAMAGE_ZONES = [
        'hood' => 'Capot',
        'front_left_fender' => 'Guarda-lamas frente esquerdo',
        'front_right_fender' => 'Guarda-lamas frente direito',
        'front_bumper' => 'Para-choques frente',
        'front_left_door' => 'Porta frente esquerda',
        'front_right_door' => 'Porta frente direita',
        'rear_left_door' => 'Porta trás esquerda',
        'rear_right_door' => 'Porta trás direita',
        'rear_left_panel' => 'Painel traseiro esquerdo',
        'rear_right_panel' => 'Painel traseiro direito',
        'trunk_lid' => 'Tampa da mala',
        'rear_bumper' => 'Para-choques traseiro',
        'roof' => 'Tejadilho',
        'right_sill' => 'Embaladeira direita',
        'left_sill' => 'Embaladeira esquerda',
    ];

    public const DEFAULT_MATERIALS = ['Tinta', 'Massa', 'Aparelho', 'Lixa', 'Lixa de tiras', 'Esfregão', 'Fita', 'Papel', 'Verniz'];

    protected $fillable = [
        'vehicle_id', 'legacy_repair_id', 'painter_id', 'status', 'client_contact', 'brand_model',
        'license', 'entry_date', 'exit_date', 'optics', 'black_parts', 'wheels', 'other_work', 'notes',
        'created_by_id', 'updated_by_id', 'completed_by_id', 'completed_at',
    ];

    protected $casts = ['entry_date' => 'date', 'exit_date' => 'date', 'completed_at' => 'datetime'];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function legacyRepair()
    {
        return $this->belongsTo(Repair::class, 'legacy_repair_id');
    }

    public function painter()
    {
        return $this->belongsTo(User::class, 'painter_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by_id');
    }

    public function completedBy()
    {
        return $this->belongsTo(User::class, 'completed_by_id');
    }

    public function damages()
    {
        return $this->hasMany(PaintingJobDamage::class);
    }

    public function materials()
    {
        return $this->hasMany(PaintingJobMaterial::class)->orderBy('position')->orderBy('id');
    }
}
