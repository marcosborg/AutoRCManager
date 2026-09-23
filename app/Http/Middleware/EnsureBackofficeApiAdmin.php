<?php

namespace App\Http\Middleware;

use App\Support\RolePreview;
use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

class EnsureBackofficeApiAdmin
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->user()?->currentAccessToken();

        abort_unless(
            $token instanceof PersonalAccessToken
                && str_starts_with($token->name, 'integration:')
                && RolePreview::isRealAdmin($request->user()),
            403,
            'É necessário um token de integração de um administrador.'
        );

        return $next($request);
    }
}
