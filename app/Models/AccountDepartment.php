<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AccountDepartment extends Model
{
    use SoftDeletes, Auditable, HasFactory;

    public $table = 'account_departments';

    protected $fillable = [
        'name',
    ];
}
