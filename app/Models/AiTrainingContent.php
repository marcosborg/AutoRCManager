<?php

namespace App\Models;

use App\Traits\Auditable;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AiTrainingContent extends Model
{
    use SoftDeletes, HasFactory, Auditable;

    public const TYPE_SELECT = [
        'instruction' => 'Instrução',
        'faq' => 'FAQ',
        'company_info' => 'Informação da empresa',
        'sales_script' => 'Script comercial',
        'financing' => 'Financiamento',
        'trade_in' => 'Retoma',
        'warranty' => 'Garantia',
        'objection' => 'Objeção',
        'other' => 'Outro',
    ];

    public $table = 'ai_training_contents';

    protected $fillable = ['assistant_id', 'title', 'type', 'content', 'active', 'sort_order'];

    protected $casts = [
        'active' => 'boolean',
    ];

    protected function serializeDate(DateTimeInterface $date): string
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function assistant()
    {
        return $this->belongsTo(AiAssistant::class, 'assistant_id');
    }
}
