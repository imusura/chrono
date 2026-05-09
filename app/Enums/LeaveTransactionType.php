<?php

declare(strict_types=1);

namespace App\Enums;

enum LeaveTransactionType: string
{
    case Usage = 'usage';
    case Adjustment = 'adjustment';
    case Carryover = 'carryover';
    case Expiry = 'expiry';
}
