<?php

namespace App\Enums;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;

enum PaymentMethodType: string
{
    case BankAccount = 'bank_account';
    case CreditCard = 'credit_card';
    case Cash = 'cash';

    public static function human(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => Str::headline($case->value)])
            ->all();
    }

    public function label(): string
    {
        return Arr::get(self::human(), $this->value);
    }
}
