<?php

namespace App\Support;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class RolePreview
{
    public static function isRealAdmin(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $user->roles()
            ->where(function ($query) {
                $query->where('roles.id', 1)
                    ->orWhere('roles.title', 'Admin');
            })
            ->exists();
    }

    public static function activeRole(): ?Role
    {
        $roleId = session('role_preview.role_id');
        if (! $roleId) {
            return null;
        }

        return Role::find($roleId);
    }

    public static function effectiveRoles(User $user): Collection
    {
        $previewRole = self::isRealAdmin($user) ? self::activeRole() : null;

        if ($previewRole) {
            return new Collection([$previewRole]);
        }

        return $user->roles;
    }

    public static function hasAnyEffectiveRole(?User $user, array $titles): bool
    {
        if (! $user) {
            return false;
        }

        return self::effectiveRoles($user)
            ->pluck('title')
            ->intersect($titles)
            ->isNotEmpty();
    }
}
