<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'is_active',
        'cash_box_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function cash_box()
    {
        return $this->belongsTo(CashBox::class);
    }
}
