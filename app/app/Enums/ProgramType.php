<?php

declare(strict_types=1);

namespace App\Enums;

enum ProgramType: int
{
    case ZAKLADNI = 1;
    case ZPROSTREDKOVANI = 2;
    case REZERVACE = 3;
}