<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchasingCompany extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'active',
        'created_by_id',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function created_by()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }
}
