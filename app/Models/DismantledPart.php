<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;
class DismantledPart extends Model {
 use Auditable;
 protected $guarded=['id'];
 protected $casts=['value'=>'decimal:2'];
 public const CONDITIONS=['good'=>'Bom estado','repair'=>'Necessita reparação','untested'=>'Por testar'];
 public function vehicle(){return $this->belongsTo(Vehicle::class)->withTrashed();}
 public function createdBy(){return $this->belongsTo(User::class,'created_by_id');}
 public function revisionToken(){return hash('sha256',json_encode([$this->getAttributes(),AuditLog::where('subject_type',self::class.'#'.$this->id)->max('id')]));}
}
