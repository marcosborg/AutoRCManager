<?php

namespace App\Domain\Ownership;

final class OwnershipType
{
    public const GROUP = 'group';
    public const EXTERNAL = 'external';

    public static function options(): array
    {
        return [
            self::GROUP,
            self::EXTERNAL,
        ];
    }
}
