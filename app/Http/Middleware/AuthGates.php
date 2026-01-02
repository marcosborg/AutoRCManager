<?php

namespace App\Http\Middleware;

use App\Models\Role;
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

        return $next($request);
    }
}
