<?php declare(strict_types=1);

namespace App\Components\Info;

use Nette\Application\UI\Control;
use App\Models\FacilityManager;

class ChangeRequestDetailControl extends Control
{
    public function __construct(
        private FacilityManager $facilityManager,
    ) {}

    public function render(int $id): void
    {
        $request = $this->facilityManager->getClinicChangeRequest($id);

        if (!$request) {
            return;
        }

        // Vyfiltrujeme pouze vyplněná data a odstraníme technické sloupce
        $data = array_filter($request->toArray(), fn($value) => $value !== null && $value !== '');
        unset($data['id'], $data['clinics_id']);

        // Překladový slovník názvů sloupců z DB pro administrátora
        $labels = [
            'name' => 'Název ordinace/kliniky',
            'address_street_number' => 'Adresa (Ulice a č.p.)',
            'address_city' => 'Město',
            'address_ZIP' => 'PSČ',
            'web' => 'Webové stránky',
            'program_type' => 'Typ programu',
            'reservation_email' => 'Rezervační e-mail',
            'reservation_phone' => 'Rezervační telefon',
            'max_patients' => 'Kapacita nových pacientů',
            'description' => 'Popis ordinace',
        ];

        $programs = $this->facilityManager->getProgramNames();

        $this->template->requestData = $data;
        $this->template->labels = $labels;
        $this->template->programs = $programs;

        $this->template->render(__DIR__ . '/ChangeRequestDetailControl.latte');
    }
}