<?php

namespace App\Enums;

enum EquipmentCondition: string
{
    case NEW = 'new';
    case NEW_LIKE = 'new_like';
    case LIKE_NEW = 'like_new'; // Alias for frontend compatibility
    case EXCELLENT = 'excellent';
    case GOOD = 'good';
    case FAIR = 'fair';
    case POOR = 'poor';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match($this) {
            self::NEW => 'New',
            self::NEW_LIKE => 'Like New',
            self::LIKE_NEW => 'Like New',
            self::EXCELLENT => 'Excellent',
            self::GOOD => 'Good',
            self::FAIR => 'Fair',
            self::POOR => 'Poor',
        };
    }
}