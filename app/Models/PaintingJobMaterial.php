<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaintingJobMaterial extends Model
{
    protected $fillable = ['painting_job_id', 'material_type', 'reference', 'quantity', 'used_date', 'hours', 'position'];

    protected $casts = ['used_date' => 'date', 'quantity' => 'decimal:2', 'hours' => 'decimal:2'];

    public function paintingJob()
    {
        return $this->belongsTo(PaintingJob::class);
    }
}
