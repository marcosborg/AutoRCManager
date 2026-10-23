<?php

namespace App\Http\Middleware;

use App\Models\Role;
use App\Support\RolePreview;
use Closure;
use Illuminate\Support\Facades\Gate;

class AuthGates
{
    public function handle($request, Closure $next)
    {
        $user = auth()->user();

        if (! $user) {
            return $next($request);
        }

        $user->setRelation('roles', RolePreview::effectiveRoles($user));

        $roles            = Role::with('permissions')->get();
        $permissionsArray = [];

        foreach ($roles as $role) {
            foreach ($role->permissions as $permissions) {
                $permissionsArray[$permissions->title][] = $role->id;
            }
        }

        foreach ($permissionsArray as $title => $roles) {
            Gate::define($title, function ($user) use ($roles) {
                return count(array_intersect($user->roles->pluck('id')->toArray(), $roles)) > 0;
            });
        }

        // Marketing Stand manages lead visibility and assignment, but is always
        // read-only in the backoffice, regardless of permissions attached in DB.
        foreach (['lead_edit', 'lead_delete'] as $readOnlyLeadAbility) {
            $allowedRoleIds = $permissionsArray[$readOnlyLeadAbility] ?? [];

            Gate::define($readOnlyLeadAbility, function ($user) use ($allowedRoleIds) {
                if ($user->roles->contains('title', 'Marketing Stand')) {
                    return false;
                }

                return count(array_intersect($user->roles->pluck('id')->toArray(), $allowedRoleIds)) > 0;
            });
        }

        $financeUserNames = ['rita', 'rafael'];
        $financePermissionTitles = ['financial_sensitive_access', 'aquisition_of_the_vehicle'];
        $financeRoleIds = [];

        foreach ($financePermissionTitles as $title) {
            $financeRoleIds = array_merge($financeRoleIds, $permissionsArray[$title] ?? []);
        }

        $financeRoleIds = array_unique($financeRoleIds);

        $financeGate = function ($user) use ($financeUserNames, $financeRoleIds) {
            $name = strtolower(trim((string) $user->name));

            if (in_array($name, $financeUserNames, true)) {
                return true;
            }

            if (empty($financeRoleIds)) {
                return false;
            }

            return count(array_intersect($user->roles->pluck('id')->toArray(), $financeRoleIds)) > 0;
        };

        Gate::define('financial_sensitive_access', $financeGate);
        Gate::define('aquisition_of_the_vehicle', $financeGate);

        // Apply explicit restrictions after inherited permissions and name-based finance grants.
        foreach (array_unique(array_merge(array_keys($permissionsArray), ['financial_sensitive_access', 'aquisition_of_the_vehicle'])) as $ability) {
            if (\App\Support\WorkshopPrivacy::commercialAbility($ability)) {
                $existing = Gate::abilities()[$ability] ?? fn () => false;
                Gate::define($ability, fn ($user) => !\App\Support\WorkshopPrivacy::restricted($user) && $existing($user));
            }
        }
        if (\App\Support\WorkshopPrivacy::restricted($user)) {
            $action = (string) $request->route()?->getActionName();
            abort_if(\App\Support\WorkshopPrivacy::blockedRoute((string) $request->route()?->getName())
                || (str_contains($action, 'GestaoApiController@') && !str_ends_with($action, '@workshop'))
                || preg_match('/\\\\(Dashboard|Cash|Approval|VehicleTimeline|VehicleNote|VehicleDocument|Suplier|Client|Sales|VehicleGroup|VehicleTradeIn|Depreciation|Appreciation|AuditLogs)(Api)?Controller@/', $action), 403);
        }

        return $next($request);
    }
}
