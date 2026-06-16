<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatChannel extends Model
{
    use HasFactory;

    public $table = 'chat_channels';

    protected $fillable = ['name', 'slug', 'active'];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function leads()
    {
        return $this->hasMany(ChatLead::class, 'channel_id');
    }

    public function conversations()
    {
        return $this->hasMany(ChatConversation::class, 'channel_id');
    }
}
