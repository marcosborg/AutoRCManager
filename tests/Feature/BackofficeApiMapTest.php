<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class BackofficeApiMapTest extends TestCase
{
    public function test_every_admin_route_has_a_protected_api_equivalent(): void
    {
        $routes = collect(Route::getRoutes()->getRoutes());
        $admin = $routes->filter(fn ($route) => $route->uri() === 'admin' || str_starts_with($route->uri(), 'admin/'));
        $api = $routes->filter(fn ($route) => $route->uri() === 'api/v1/backoffice' || str_starts_with($route->uri(), 'api/v1/backoffice/'));

        $key = fn ($route, string $prefix) => implode('|', array_diff($route->methods(), ['HEAD']))
            .' '.substr($route->uri(), strlen($prefix))
            .' '.ltrim($route->getActionName(), '\\');

        $adminKeys = $admin->map(fn ($route) => $key($route, 'admin'))->sort()->values()->all();
        $apiKeys = $api->map(fn ($route) => $key($route, 'api/v1/backoffice'))->sort()->values()->all();

        $this->assertNotEmpty($adminKeys);
        $this->assertSame($adminKeys, $apiKeys);
        $api->each(function ($route) {
            $this->assertContains('auth:sanctum', $route->gatherMiddleware());
            $this->assertContains('backoffice.api.admin', $route->gatherMiddleware());
            $this->assertContains('backoffice.api.response', $route->gatherMiddleware());
            [$class, $method] = explode('@', ltrim($route->getActionName(), '\\'), 2);
            $this->assertTrue(method_exists($class, $method), $route->uri().' -> '.$class.'::'.$method);
        });
    }

    public function test_backoffice_api_rejects_requests_without_a_token(): void
    {
        $this->getJson('/api/v1/backoffice')->assertUnauthorized();
    }

    public function test_view_responses_are_serialized_as_json(): void
    {
        Route::middleware('backoffice.api.response')->get('/api/_backoffice-view-test', fn () => view('welcome', ['sample' => 'ready']));

        $this->getJson('/api/_backoffice-view-test')
            ->assertOk()
            ->assertJsonPath('view', 'welcome')
            ->assertJsonPath('data.sample', 'ready');
    }

    public function test_redirect_responses_are_serialized_as_json(): void
    {
        Route::middleware([
            \Illuminate\Session\Middleware\StartSession::class,
            'backoffice.api.response',
        ])->post('/api/_backoffice-redirect-test', fn () => redirect('/admin')->with('message', 'Concluído'));

        $this->postJson('/api/_backoffice-redirect-test')
            ->assertOk()
            ->assertJsonPath('message', 'Concluído');
    }

    public function test_form_error_redirects_become_validation_errors(): void
    {
        Route::middleware([
            \Illuminate\Session\Middleware\StartSession::class,
            'backoffice.api.response',
        ])->post('/api/_backoffice-error-test', fn () => redirect('/admin')->withErrors(['field' => 'Obrigatório']));

        $this->postJson('/api/_backoffice-error-test')
            ->assertUnprocessable()
            ->assertJsonPath('errors.field.0', 'Obrigatório');
    }

    public function test_profile_and_password_reset_api_validate_access_and_input(): void
    {
        $this->getJson('/api/v1/profile')->assertUnauthorized();
        $this->postJson('/api/v1/auth/forgot-password', ['email' => 'invalid'])->assertUnprocessable();
        $this->postJson('/api/v1/auth/reset-password', [])->assertUnprocessable();
        $this->getJson('/api/v1/lead-access/invalid')->assertNotFound();
    }
}
