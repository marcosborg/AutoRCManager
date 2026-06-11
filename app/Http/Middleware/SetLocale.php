<?php

namespace App\Http\Middleware;

use Closure;

class SetLocale
{
    public function handle($request, Closure $next)
    {
        app()->setLocale('pt');
        session()->forget('language');

        return $next($request);
    }
}
