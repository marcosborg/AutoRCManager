<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\ViewErrorBag;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\RedirectResponse;

class BackofficeApiResponse
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if ($response instanceof RedirectResponse) {
            $errors = session('errors');
            $hasCurrentErrors = in_array('errors', session('_flash.new', []), true);
            if ($hasCurrentErrors && $errors instanceof ViewErrorBag && $errors->count() > 0) {
                return response()->json([
                    'message' => 'Os dados fornecidos são inválidos.',
                    'errors' => $errors->getBag('default')->toArray(),
                ], 422);
            }

            return response()->json([
                'message' => session('message') ?: 'Operação concluída.',
                'redirect_to' => $response->getTargetUrl(),
            ]);
        }

        if ($response instanceof Response && $response->getOriginalContent() instanceof View) {
            /** @var View $view */
            $view = $response->getOriginalContent();

            return response()->json([
                'view' => $view->name(),
                'data' => $view->getData(),
            ], $response->getStatusCode());
        }

        return $response;
    }
}
