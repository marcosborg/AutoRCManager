<?php

namespace App\Domain\Consignments;

final class ConsignmentStatus
{
    public const ACTIVE = 'active';
    public const CLOSED = 'closed';

    public static function options(): array
    {
        return [
            self::ACTIVE,
            self::CLOSED,
        ];
    }
}
