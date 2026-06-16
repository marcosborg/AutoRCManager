<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeadSalesRotation extends Model
{
    use HasFactory;

    public $table = 'lead_sales_rotation';

    protected $fillable = [
        'last_user_id',
    ];

    public function last_user()
    {
        return $this->belongsTo(User::class, 'last_user_id');
    }
}
