<?php declare(strict_types=1);

namespace App\Components\Login;

interface LoginControlFactory
{
    public function create(): LoginControl;
}