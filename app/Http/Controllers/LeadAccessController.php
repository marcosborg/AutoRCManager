<?php

namespace App\Http\Controllers;

use App\Models\LeadAccessToken;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LeadAccessController extends Controller
{
    public function show(Request $request, string $token)
    {
        abort_if(! preg_match('/^[A-Za-z0-9]{40,120}$/', $token), Response::HTTP_NOT_FOUND);

        $accessToken = LeadAccessToken::with(['lead.assigned_user', 'user'])
            ->where('token_hash', hash('sha256', $token))
            ->first();

        abort_if(! $accessToken || ! $accessToken->isUsable(), Response::HTTP_NOT_FOUND);

        $accessToken->update(['last_used_at' => now()]);

        $lead = $accessToken->lead;
        $messages = collect(data_get($lead->raw_data, 'messages', []));
        if (data_get($lead->raw_data, 'source') !== 'ai_whatsapp') {
            $messages = collect();
        }

        return view('leadAccess.show', compact('lead', 'accessToken', 'messages'));
    }
}
