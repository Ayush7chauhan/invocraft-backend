<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Paid          = 'paid';
    case PartiallyPaid = 'partially_paid';
    case Unpaid        = 'unpaid';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
