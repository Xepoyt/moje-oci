<?php
declare(strict_types=1);
namespace App\Components\Account;

interface ChangeContactControlFactory {
    public function create(): ChangeContactControl;
}