<?php declare(strict_types=1);

namespace App\Components\Info;


interface ClinicDetailControlFactory
{
    public function create(): ClinicDetailControl;
}