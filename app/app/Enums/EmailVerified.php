<?php

declare(strict_types=1);

namespace App\Enums;

enum EmailVerified: int
{
    case UNVERIFIED = 0;
    case VERIFIED = 1;
    case EDITING = 2;
}