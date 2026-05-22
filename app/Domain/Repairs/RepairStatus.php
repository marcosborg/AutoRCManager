<?php

namespace App\Domain\Repairs;

use Carbon\CarbonInterface;

final class RepairStatus
{
    public const CLOSED_ID = 3;

    public static function isOpen(?int $repairStateId, CarbonInterface|string|null $repairFinishedAt = null): bool
    {
        return ($repairStateId === null || $repairStateId !== self::CLOSED_ID) && $repairFinishedAt === null;
    }
}
