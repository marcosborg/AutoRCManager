<?php

namespace App\Models;

use App\Traits\Auditable;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class PartReceipt extends Model implements HasMedia
{
    use SoftDeletes, InteractsWithMedia, HasFactory, Auditable;

    protected $fillable = [
        'part_order_id',
        'received_at',
        'received_location',
        'received_by_id',
        'signature_name',
        'observations',
    ];

    protected $appends = [
        'attachments',
    ];

    protected $dates = [
        'received_at',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('thumb')->fit('crop', 50, 50);
        $this->addMediaConversion('preview')->fit('crop', 120, 120);
    }

    public function part_order()
    {
        return $this->belongsTo(PartOrder::class, 'part_order_id');
    }

    public function received_by()
    {
        return $this->belongsTo(User::class, 'received_by_id');
    }

    public function getAttachmentsAttribute()
    {
        return $this->getMedia('attachments');
    }
}
