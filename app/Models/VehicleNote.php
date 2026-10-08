<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;

class VehicleNote extends Model
{
    use Auditable;

    protected $fillable = ['vehicle_id', 'author_id', 'author_name', 'body', 'submission_token'];

    public function revisions()
    {
        return AuditLog::where('description', 'vehicle_note:edited')
            ->where('subject_id', $this->id)->where('subject_type', self::class.'#'.$this->id);
    }

    public function revisionToken(): string
    {
        return hash('sha256', json_encode([$this->body, $this->revisions()->max('id')]));
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id')->withTrashed();
    }
}
