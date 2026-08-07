<?php

declare(strict_types=1);

namespace App\Enums;

enum FilterStatus: int
{
    case UNVERIFIED = 0;
    case APPROVED = 1;
    case PENDING_CHANGES = 2;
    case DENIED = 3;
    case PENDING_APPROVAL = 4;

    public function getLabel(): string
    {
        return match($this) {
            self::UNVERIFIED => 'Neověřeno',
            self::APPROVED => 'Schváleno',
            self::PENDING_CHANGES => 'Žádá o změnu',
            self::DENIED => 'Zamítnuto',
            self::PENDING_APPROVAL => 'Čeká na schválení',
        };
    }
    
    public static function getOptionsForForm(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->getLabel();
        }
        return $options;
    }
}