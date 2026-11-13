<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Unconfirmed = 'unconfirmed';
    case Pending = 'pending';
    case AwaitingBuyer = 'awaiting_buyer';
    case Disputed = 'disputed';
    case Completed = 'completed';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Status yang masih berjalan, dipakai penjaga hapus user (PRD BR-21).
     *
     * @return array<int, string>
     */
    public static function active(): array
    {
        return array_map(
            fn (self $status) => $status->value,
            [self::Unconfirmed, self::Pending, self::AwaitingBuyer, self::Disputed],
        );
    }
}
