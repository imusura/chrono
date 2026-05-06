<?php

namespace App\Enums;

enum ProjectRole: string
{
    case Admin = 'admin';
    case Agent = 'agent';
    case Client = 'client';
}
