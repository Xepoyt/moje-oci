<?php
declare(strict_types=1);
namespace App\Components\Account;

interface ChangeClinicControlFactory {
    public function create(): ChangeClinicControl;
}