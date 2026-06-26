<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Support\RolePreview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\HttpFoundation\Response;

class SystemMaintenanceController extends Controller
{
    private const COMMANDS = [
        'config-clear' => [
            'label' => 'Limpar configuracao',
            'commands' => ['config:clear'],
        ],
        'cache-clear' => [
            'label' => 'Limpar cache',
            'commands' => ['cache:clear'],
        ],
        'clear-all' => [
            'label' => 'Limpar configuracao e cache',
            'commands' => ['config:clear', 'cache:clear'],
        ],
    ];

    public function index()
    {
        $this->authorizeRealAdmin();

        return view('admin.system-maintenance.index', [
            'mailConfig' => $this->mailConfig(),
            'leadConfig' => $this->leadConfig(),
        ]);
    }

    public function run(Request $request)
    {
        $this->authorizeRealAdmin();

        $data = $request->validate([
            'action' => ['required', 'in:' . implode(',', array_keys(self::COMMANDS))],
        ]);

        $definition = self::COMMANDS[$data['action']];
        $outputs = [];

        foreach ($definition['commands'] as $command) {
            Artisan::call($command);
            $outputs[] = trim(Artisan::output()) ?: "{$command}: OK";
        }

        $this->audit($request, $data['action'], $definition['commands']);

        return redirect()
            ->route('admin.system-maintenance.index')
            ->with('message', $definition['label'] . ' executado.')
            ->with('command_output', implode("\n", $outputs));
    }

    private function authorizeRealAdmin(): void
    {
        abort_if(! RolePreview::isRealAdmin(auth()->user()), Response::HTTP_FORBIDDEN, '403 Forbidden');
    }

    private function mailConfig(): array
    {
        $defaultMailer = config('mail.default');
        $smtp = config('mail.mailers.smtp', []);
        $from = config('mail.from', []);

        return [
            'MAIL_MAILER' => $defaultMailer,
            'MAIL_HOST' => $smtp['host'] ?? null,
            'MAIL_PORT' => $smtp['port'] ?? null,
            'MAIL_ENCRYPTION' => $smtp['encryption'] ?? null,
            'MAIL_USERNAME' => $this->mask(config('mail.mailers.smtp.username')),
            'MAIL_FROM_ADDRESS' => $from['address'] ?? null,
            'MAIL_FROM_NAME' => $from['name'] ?? null,
        ];
    }

    private function leadConfig(): array
    {
        return [
            'AI_LEAD_DELIVERY_CHANNEL' => config('ai_assistant.lead_delivery_channel'),
            'AI_LEAD_EMAIL_CC_ADDRESSES' => implode(', ', config('ai_assistant.lead_email_cc_addresses', [])),
        ];
    }

    private function mask(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        if (! str_contains($value, '@')) {
            return str_repeat('*', min(strlen($value), 8));
        }

        [$local, $domain] = explode('@', $value, 2);
        $visible = substr($local, 0, 2);

        return $visible . str_repeat('*', max(strlen($local) - 2, 3)) . '@' . $domain;
    }

    private function audit(Request $request, string $action, array $commands): void
    {
        try {
            AuditLog::create([
                'description' => 'system_maintenance_command',
                'subject_id' => optional($request->user())->id,
                'subject_type' => optional($request->user())->getMorphClass(),
                'user_id' => optional($request->user())->id,
                'properties' => collect([
                    'action' => $action,
                    'commands' => $commands,
                    'user_agent' => $request->userAgent(),
                ]),
                'host' => $request->ip(),
            ]);
        } catch (\Throwable $exception) {
            report($exception);
        }
    }
}
