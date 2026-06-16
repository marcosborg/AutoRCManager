<?php

namespace App\Models;

use App\Traits\Auditable;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AiAssistant extends Model
{
    use SoftDeletes, HasFactory, Auditable;

    public $table = 'ai_assistants';

    protected $fillable = [
        'name',
        'slug',
        'company_name',
        'commercial_phone',
        'active',
        'system_prompt',
        'rules',
        'forbidden_topics',
        'allowed_topics',
        'escalation_rules',
        'default_language',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    protected function serializeDate(DateTimeInterface $date): string
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function training_contents()
    {
        return $this->hasMany(AiTrainingContent::class, 'assistant_id');
    }

    public function conversations()
    {
        return $this->hasMany(ChatConversation::class, 'assistant_id');
    }
}
