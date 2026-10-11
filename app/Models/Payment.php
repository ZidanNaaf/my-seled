<?php
declare(strict_types=1);

namespace App\Models;

class Payment
{
    public const METHODS = [
        'cash_on_arrival' => 'Bayar saat toilet tiba',
        'bank_transfer' => 'Transfer bank (simulasi)',
        'ewallet' => 'E-wallet (simulasi)',
    ];
}
