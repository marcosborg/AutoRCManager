<?php

namespace App\Services;

use App\Mail\PartOrderCreatedMail;
use App\Mail\PartOrderDelayedMail;
use App\Models\PartOrder;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class PartOrderNotificationService
{
    private const RECIPIENT_ROLES = ['Chefe oficina', 'Aux. oficina', 'Aux. Oficina'];

    public function sendCreated(PartOrder $partOrder): void
    {
        $this->send($partOrder, new PartOrderCreatedMail($partOrder));
    }

    public function sendDelayed(PartOrder $partOrder): void
    {
        $this->send($partOrder, new PartOrderDelayedMail($partOrder));
    }

    private function send(PartOrder $partOrder, $mail): void
    {
        $emails = $this->recipientEmails();

        if ($emails->isEmpty()) {
            Log::warning('Part order notification has no recipients.', [
                'part_order_id' => $partOrder->id,
                'roles' => self::RECIPIENT_ROLES,
            ]);
            return;
        }

        try {
            Mail::to($emails->all())->send($mail);
        } catch (\Throwable $exception) {
            Log::error('Part order notification email failed.', [
                'part_order_id' => $partOrder->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function recipientEmails()
    {
        return User::query()
            ->whereNotNull('email')
            ->whereHas('roles', function ($query) {
                $query->whereIn('title', self::RECIPIENT_ROLES);
            })
            ->pluck('email')
            ->filter()
            ->map(fn ($email) => trim((string) $email))
            ->filter()
            ->unique()
            ->values();
    }
}
