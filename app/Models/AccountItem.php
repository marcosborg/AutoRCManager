<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AccountItem extends Model
{
    use SoftDeletes, Auditable, HasFactory;

    public $table = 'account_items';

    protected $fillable = [
        'name',
        'type',
        'total',
        'account_category_id',
    ];

    public function account_category()
    {
        return $this->belongsTo(AccountCategory::class, 'account_category_id');
    }
}
