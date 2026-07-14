<?php

namespace App\Models;

use App\Support\RolePreview;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CalendarTask extends Model
{
    use HasFactory;

    public const GROUP_MANAGEMENT = 'management';

    public const GROUP_WORKSHOP = 'workshop';

    public const TYPE_IMPORT_DEADLINE = 'vehicle_import_deadline';

    public const TYPE_IPO_DOCUMENTS_READY = 'vehicle_ipo_documents_ready';

    public const TYPE_NEW_LICENSE_WORKSHOP = 'vehicle_new_license_workshop';

    public const TYPE_NEW_LICENSE_TOLLS = 'vehicle_new_license_tolls';

    public $table = 'calendar_tasks';

    protected $dates = [
        'due_date',
        'completed_at',
        'created_at',
        'updated_at',
    ];

    protected $fillable = [
        'title',
        'due_date',
        'notes',
        'vehicle_id',
        'recipient_group',
        'assigned_to_id',
        'type',
        'dedupe_key',
        'target_url',
        'created_by_id',
        'completed_at',
    ];

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function getDueDateAttribute($value)
    {
        return $value ? Carbon::parse($value)->format(config('panel.date_format')) : null;
    }

    public function setDueDateAttribute($value): void
    {
        $this->attributes['due_date'] = $value ? Carbon::createFromFormat(config('panel.date_format'), $value)->format('Y-m-d') : null;
    }

    public function created_by()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function assigned_to()
    {
        return $this->belongsTo(User::class, 'assigned_to_id');
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function scopeVisibleTo(Builder $query, ?User $user): Builder
    {
        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        $canSeeManagement = RolePreview::hasAnyEffectiveRole($user, ['Admin', 'Adm', 'Gestão', 'Gestao', 'Aux. gestão', 'Aux. gestao']);
        $canSeeWorkshop = RolePreview::hasAnyEffectiveRole($user, ['Admin', 'Adm', 'Chefe oficina', 'Aux. oficina', 'Aux. Oficina']);

        return $query->where(function (Builder $visibilityQuery) use ($user, $canSeeManagement, $canSeeWorkshop): void {
            $visibilityQuery->where(function (Builder $legacyTaskQuery) use ($user): void {
                $legacyTaskQuery
                    ->where('created_by_id', $user->id)
                    ->whereNull('recipient_group')
                    ->whereNull('assigned_to_id')
                    ->whereNull('type');
            })->orWhere('assigned_to_id', $user->id);

            if ($canSeeManagement) {
                $visibilityQuery->orWhere('recipient_group', self::GROUP_MANAGEMENT);
            }

            if ($canSeeWorkshop) {
                $visibilityQuery->orWhere('recipient_group', self::GROUP_WORKSHOP);
            }
        });
    }

    public function isVisibleTo(?User $user): bool
    {
        return self::query()->whereKey($this->getKey())->visibleTo($user)->exists();
    }
}
