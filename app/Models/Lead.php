<?php

namespace App\Models;

use App\Traits\Auditable;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lead extends Model
{
    use SoftDeletes, HasFactory, Auditable;

    public const STATUS_NEW = 'new';
    public const STATUS_CONTACTED = 'contacted';
    public const STATUS_QUALIFIED = 'qualified';
    public const STATUS_WON = 'won';
    public const STATUS_LOST = 'lost';

    public const STATUS_SELECT = [
        self::STATUS_NEW => 'Novo',
        self::STATUS_CONTACTED => 'Contactado',
        self::STATUS_QUALIFIED => 'Qualificado',
        self::STATUS_WON => 'Ganho',
        self::STATUS_LOST => 'Perdido',
    ];

    public $table = 'leads';

    protected $fillable = [
        'leadgen_id',
        'page_id',
        'form_id',
        'ad_id',
        'adgroup_id',
        'full_name',
        'first_name',
        'last_name',
        'email',
        'phone',
        'vehicle_interest',
        'budget',
        'financing',
        'trade_in',
        'raw_data',
        'assigned_user_id',
        'status',
    ];

    protected $casts = [
        'raw_data' => 'array',
    ];

    protected function serializeDate(DateTimeInterface $date): string
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function assigned_user()
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function assignment_histories()
    {
        return $this->hasMany(LeadAssignmentHistory::class);
    }

    public function notes()
    {
        return $this->hasMany(LeadNote::class);
    }
}
