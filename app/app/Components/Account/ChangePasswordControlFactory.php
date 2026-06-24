<?php
declare(strict_types=1);
namespace App\Components\Account;

interface ChangePasswordControlFactory {
    public function create(int $clinicId): ChangePasswordControl;
}