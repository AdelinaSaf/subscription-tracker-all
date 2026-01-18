<?php

namespace App\Enum;

enum RolesEnum: string
{
    case ROLE_ROOT = 'ROLE_ROOT';
    case ROLE_ADMIN = 'ROLE_ADMIN';
    case ROLE_USER = 'ROLE_USER';
}

