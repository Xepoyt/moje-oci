<?php declare(strict_types=1);

namespace App\Components\Login;

interface ForgottenPasswordControlFactory
{
    public function create(): ForgottenPasswordControl;
}