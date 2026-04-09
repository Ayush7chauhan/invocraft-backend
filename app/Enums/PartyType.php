<?php

namespace App\Enums;

enum PartyType: string
{
    case Customer = 'customer';
    case Supplier = 'supplier';
    case Both     = 'both';

    public function label(): string
    {
        return match($this) {
            self::Customer => 'Customer',
            self::Supplier => 'Supplier',
            self::Both     => 'Customer & Supplier',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
