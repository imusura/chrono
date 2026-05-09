<?php

declare(strict_types=1);

namespace App\Enums;

enum VacationMode: string
{
    case Simple = 'simple';
    case Workflow = 'workflow';
}
