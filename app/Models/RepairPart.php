<?php

namespace App\Models;

use App\Traits\Auditable;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RepairPart extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    public $table = 'repair_parts';

    protected $dates = [
        'part_date',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $fillable = [
        'repair_id',
        'supplier',
        'invoice_number',
        'part_date',
        'part_name',
        'amount',
    ];

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function repair()
    {
        return $this->belongsTo(Repair::class, 'repair_id');
    }
}

