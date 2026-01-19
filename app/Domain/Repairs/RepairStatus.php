<?php

namespace App\Domain\Repairs;

final class RepairStatus
{
    public const CLOSED_ID = 3;

    public static function isOpen(?int $repairStateId): bool
    {
        return $repairStateId === null || $repairStateId !== self::CLOSED_ID;
    }
}
