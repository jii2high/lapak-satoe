<?php

namespace App\Enums;

enum StoreStatus: string
{
    case Closed = 'closed';
    case Open = 'open';
    case Closing = 'closing';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
