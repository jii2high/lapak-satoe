<?php

namespace App\Enums;

enum Role: string
{
    case Buyer = 'buyer';
    case Seller = 'seller';
    case Admin = 'admin';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::Buyer => 'Pembeli',
            self::Seller => 'Penjual',
            self::Admin => 'Admin',
        };
    }
}
