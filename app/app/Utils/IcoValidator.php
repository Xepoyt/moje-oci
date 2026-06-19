<?php

namespace App\Utils;

use Nette\Forms\Control;

class IcoValidator
{
    public static function validate(string $ico): bool
    {
        // 1. Odstraníme případné bílé znaky (mezery)
        $ico = preg_replace('#\s+#', '', $ico);

        // 2. IČO musí mít přesně 8 číslic
        if (!preg_match('#^\d{8}$#', $ico)) {
            return false;
        }

        // 3. Výpočet váženého součtu prvních 7 číslic
        $sum = 0;
        $weights = [8, 7, 6, 5, 4, 3, 2];
        
        for ($i = 0; $i < 7; $i++) {
            $sum += (int)$ico[$i] * $weights[$i];
        }

        // 4. Výpočet zbytku po dělení 11
        $remainder = $sum % 11;

        // 5. Určení kontrolní číslice podle oficiálních pravidel
        if ($remainder === 0) {
            $controlDigit = 1;
        } elseif ($remainder === 1) {
            $controlDigit = 0;
        } else {
            $controlDigit = 11 - $remainder;
        }

        // 6. Porovnáme výsledek s poslední (8.) číslicí v IČO
        return (int)$ico[7] === $controlDigit;
    }

    public static function validateNette(Control $control): bool
    {
        // Vytáhneme hodnotu z objektu a rovnou vymažeme mezery
        $cleanIco = preg_replace('#\s+#', '', (string) $control->getValue());

        // Pokud je pole prázdné, nevalidujeme kontrolní součet (o prázdnotu se stará setRequired)
        if ($cleanIco === '') {
            return true;
        }

        // Zavoláme tvou původní validační logiku
        return self::validate($cleanIco);
    }
}