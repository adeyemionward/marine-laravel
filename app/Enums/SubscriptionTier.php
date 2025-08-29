<?php

namespace App\Enums;

enum SubscriptionTier: string
{
    case FREEMIUM = 'freemium';
    case PREMIUM = 'premium';
    case ENTERPRISE = 'enterprise';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match($this) {
            self::FREEMIUM => 'Freemium',
            self::PREMIUM => 'Premium',
            self::ENTERPRISE => 'Enterprise',
        };
    }
}