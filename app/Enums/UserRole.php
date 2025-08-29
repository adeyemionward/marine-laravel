<?php

namespace App\Enums;

enum UserRole: string
{
    case ADMIN = 'admin';
    case USER = 'user';
    case MODERATOR = 'moderator';
    case SELLER = 'seller';
    case BUYER = 'buyer';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match($this) {
            self::ADMIN => 'Administrator',
            self::USER => 'User',
            self::MODERATOR => 'Moderator',
            self::SELLER => 'Seller',
            self::BUYER => 'Buyer',
        };
    }
}