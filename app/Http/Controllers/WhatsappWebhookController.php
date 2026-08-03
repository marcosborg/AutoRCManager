<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessWhatsappWebhook;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class WhatsappWebhookController extends Controller
{
    public function verify(Request $request)
    {
        if ($request->query('hub_mode') !== 'subscribe'
            || ! hash_equals((string) config('whatsapp.verify_token'), (string) $request->query('hub_verify_token'))) {
            abort(Response::HTTP_FORBIDDEN);
        }

        return response((string) $request->query('hub_challenge'), Response::HTTP_OK)
            ->header('Content-Type', 'text/plain');
    }

    public function receive(Request $request)
    {
        $secret = (string) config('whatsapp.app_secret');
        $provided = (string) $request->header('X-Hub-Signature-256');
        $expected = 'sha256=' . hash_hmac('sha256', $request->getContent(), $secret);

        if ($secret === '' || $provided === '' || ! hash_equals($expected, $provided)) {
            abort(Response::HTTP_FORBIDDEN);
        }

        ProcessWhatsappWebhook::dispatch($request->json()->all());

        return response()->json(['ok' => true]);
    }
}
