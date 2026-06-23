<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OficinaExpertiseProcessHistory extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'process_id',
        'old_status',
        'new_status',
        'changed_by_id',
        'notes',
        'created_at',
    ];

    protected $dates = ['created_at'];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function process()
    {
        return $this->belongsTo(OficinaExpertiseProcess::class, 'process_id');
    }

    public function changed_by()
    {
        return $this->belongsTo(User::class, 'changed_by_id');
    }
}
