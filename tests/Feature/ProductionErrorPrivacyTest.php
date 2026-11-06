<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ProductionErrorPrivacyTest extends TestCase
{
    public function test_public_site_never_exposes_debug_details_in_html_or_json(): void
    {
        Route::get('/error-privacy-test', function () {
            throw new \RuntimeException('PRIVATE-EXCEPTION-CONTENT');
        });
        foreach ([false, true] as $json) {
            config(['app.debug' => true]);
            $response = $json
                ? $this->getJson('https://autorcmanager.pt/error-privacy-test')
                : $this->get('https://autorcmanager.pt/error-privacy-test');
            $response->assertStatus(500)->assertDontSee('PRIVATE-EXCEPTION-CONTENT')
                ->assertDontSee('ProductionErrorPrivacyTest.php')->assertDontSee('stack_trace');
            if ($json) $response->assertExactJson(['message' => 'Server Error']);
        }
    }
}
