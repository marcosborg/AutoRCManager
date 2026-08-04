<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaintingJobDamage extends Model
{
    protected $fillable = ['painting_job_id', 'zone', 'intensity'];

    public function paintingJob()
    {
        return $this->belongsTo(PaintingJob::class);
    }
}
