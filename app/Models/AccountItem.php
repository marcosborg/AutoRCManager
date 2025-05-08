<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AccountItem extends Model
{
    use SoftDeletes, HasFactory;

    public $table = 'account_items';

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public const TYPE_RADIO = [
        'outcome' => 'Despesa',
        'income'  => 'Ganho',
    ];

    protected $fillable = [
        'name',
        'account_category_id',
        'type',
        'total',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function account_category()
    {
        return $this->belongsTo(AccountCategory::class, 'account_category_id');
    }
}
