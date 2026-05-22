<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class SystemShutdownController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'confirmation' => ['required', 'in:DESLIGAR'],
        ]);

        try {
            AuditLog::create([
                'description'  => 'system_shutdown',
                'subject_id'   => optional($request->user())->id,
                'subject_type' => optional($request->user())->getMorphClass(),
                'user_id'      => optional($request->user())->id,
                'properties'   => collect([
                    'confirmation' => $request->input('confirmation'),
                    'user_agent'   => $request->userAgent(),
                ]),
                'host'         => $request->ip(),
            ]);
        } catch (\Throwable $exception) {
            report($exception);
        }

        Artisan::call('down', [
            '--render' => 'errors::503',
        ]);

        return response('', 503);
    }
}
