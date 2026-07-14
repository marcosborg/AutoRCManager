<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OperationalAlertRecipient extends Model
{
    use HasFactory;

    public const KEY_TOLLS = 'tolls';

    protected $fillable = [
        'key',
        'user_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
