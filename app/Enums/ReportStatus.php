<?php

namespace App\Enums;

enum ReportStatus: string
{
    case New = 'new';
    case Processed = 'processed';
    case Rejected = 'rejected';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
