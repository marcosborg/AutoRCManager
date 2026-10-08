<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;

class VehicleNote extends Model
{
    use Auditable;

    protected $fillable = ['vehicle_id', 'author_id', 'author_name', 'body', 'submission_token'];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id')->withTrashed();
    }
}
