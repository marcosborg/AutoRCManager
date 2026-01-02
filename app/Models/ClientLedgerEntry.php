<?php

namespace App\Models;

use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class ClientLedgerEntry extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    public $table = 'client_ledger_entries';

    protected $dates = [
        'entry_date',
        'created_at',
        'updated_at',
    ];

    protected $fillable = [
        'client_id',
        'vehicle_id',
        'entry_type',
        'amount',
        'entry_date',
        'description',
        'notes',
        'created_at',
        'updated_at',
    ];

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }

    public function getEntryDateAttribute($value)
    {
        return $value ? Carbon::parse($value)->format(config('panel.date_format')) : null;
    }

    public function setEntryDateAttribute($value)
    {
        $this->attributes['entry_date'] = $value
            ? Carbon::createFromFormat(config('panel.date_format'), $value)->format('Y-m-d')
            : null;
    }

    public function getAttachmentAttribute()
    {
        return $this->getMedia('attachment');
    }
}
