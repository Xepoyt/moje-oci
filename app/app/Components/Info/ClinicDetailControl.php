<?php declare(strict_types=1);

namespace App\Components\Info;

use Nette\Application\UI\Control;
use App\Models\FacilityManager;

class ClinicDetailControl extends Control
{
    public function __construct(
        private FacilityManager $facilityManager,
    ) {}

    public function render(int $id): void
    {
        // Načteme data z tabulky 'clinics' podle ID
        $clinic = $this->facilityManager->getClinic($id);

        // Pokud klinika s tímto ID neexistuje, nevykreslíme raději nic
        if (!$clinic) {
            return;
        }

        // Definice textů pro typy programů (uprav si podle potřeby)
        $programs = [
            1 => 'Základní program',
            2 => 'Rozšířený program',
            3 => 'Přímé rezervace termínů',
        ];

        // Předáme data do šablony komponenty
        $this->template->userData = $clinic;
        $this->template->programs = $programs;

        // Vykreslíme šablonu (předpokládáme, že je ve stejné složce)
        $this->template->render(__DIR__ . '/ClinicDetailControl.latte');
    }
}