<?php

namespace App\Enums;

enum ListingType: string
{
    case SALE = 'sale';
    case LEASE = 'lease';
    case RENT = 'rent';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match($this) {
            self::SALE => 'For Sale',
            self::LEASE => 'For Lease',
            self::RENT => 'For Rent',
        };
    }
}
