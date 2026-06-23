<?php declare(strict_types=1);

namespace App\Components\Info;

interface ChangeRequestDetailControlFactory
{
    public function create(): ChangeRequestDetailControl;
}