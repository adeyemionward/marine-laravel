<?php

namespace App\Enums;

enum ListingStatus: string
{
    case DRAFT = 'draft';
    case PENDING = 'pending';
    case ACTIVE = 'active';
    case SOLD = 'sold';
    case ARCHIVED = 'archived';
    case REJECTED = 'rejected';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match($this) {
            self::DRAFT => 'Draft',
            self::PENDING => 'Pending Review',
            self::ACTIVE => 'Active',
            self::SOLD => 'Sold',
            self::ARCHIVED => 'Archived',
            self::REJECTED => 'Rejected',
        };
    }
}