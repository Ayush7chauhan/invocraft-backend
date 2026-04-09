<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Cash         = 'cash';
    case Upi          = 'upi';
    case BankTransfer = 'bank_transfer';
    case Cheque       = 'cheque';
    case Other        = 'other';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
