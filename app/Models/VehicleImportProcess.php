<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VehicleImportProcess extends Model
{
    use HasFactory;

    public const DECISION_LEGALIZE = 'legalize';

    public const DECISION_SCRAP = 'scrap';

    protected $fillable = [
        'vehicle_id',
        'decision',
        'decision_at',
        'deadline_at',
        'agency_documents_sent_at',
        'documents_received_at',
        'previous_license',
        'new_license',
        'new_license_received_at',
        'scrapped_at',
        'created_by_id',
        'updated_by_id',
    ];

    protected $casts = [
        'decision_at' => 'datetime',
        'deadline_at' => 'date',
        'agency_documents_sent_at' => 'datetime',
        'documents_received_at' => 'datetime',
        'new_license_received_at' => 'datetime',
        'scrapped_at' => 'datetime',
    ];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function created_by()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function updated_by()
    {
        return $this->belongsTo(User::class, 'updated_by_id');
    }
}
