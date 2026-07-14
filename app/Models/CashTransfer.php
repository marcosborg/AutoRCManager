<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class CashTransfer extends Model implements HasMedia
{
    use Auditable, HasFactory, InteractsWithMedia;

    protected $fillable = [
        'from_cash_box_id',
        'to_cash_box_id',
        'amount',
        'occurred_at',
        'created_by_id',
        'notes',
        'group_id',
    ];

    protected $casts = [
        'amount' => 'float',
        'occurred_at' => 'datetime',
    ];

    public function from_cash_box()
    {
        return $this->belongsTo(CashBox::class, 'from_cash_box_id');
    }

    public function to_cash_box()
    {
        return $this->belongsTo(CashBox::class, 'to_cash_box_id');
    }

    public function created_by()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function operations()
    {
        return $this->hasMany(AccountOperation::class);
    }

    public function getProofsAttribute()
    {
        return $this->getMedia('proofs');
    }
}
