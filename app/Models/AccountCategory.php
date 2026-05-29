<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AccountCategory extends Model
{
    use SoftDeletes, Auditable, HasFactory;

    public $table = 'account_categories';

    protected $fillable = [
        'name',
        'account_department_id',
    ];

    public function account_department()
    {
        return $this->belongsTo(AccountDepartment::class, 'account_department_id');
    }
}
