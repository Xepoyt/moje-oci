<?php

declare(strict_types=1);

namespace App\Enums;

enum Approved: int
{
    case UNAPPROVED = 0;
    case APPROVED = 1;
    case PENDING_CHANGES = 2;
    case DENIED = 3;
}