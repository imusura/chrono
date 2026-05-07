<?php

declare(strict_types=1);

namespace App\Enums;

enum TimeEntryMode: string
{
    case Range = 'range';
    case Duration = 'duration';
}
