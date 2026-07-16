<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkshopState extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'position',
        'is_active',
        'is_default',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_default' => 'boolean',
    ];

    public function vehicles()
    {
        return $this->hasMany(Vehicle::class);
    }

    public static function default(): ?self
    {
        return static::query()->where('is_default', true)->first();
    }
}
