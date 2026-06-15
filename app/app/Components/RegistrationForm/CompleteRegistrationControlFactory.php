<?php
declare(strict_types=1);
namespace App\Components\RegistrationForm;

interface CompleteRegistrationControlFactory {
    public function create(int $clinicId): CompleteRegistrationControl;
}