<?php

namespace App\Enums;

enum PaymentMethodType: string
{
    case BankAccount = 'bank_account';
    case CreditCard = 'credit_card';
    case Cash = 'cash';
}
