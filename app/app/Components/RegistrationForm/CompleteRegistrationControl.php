<?php

declare(strict_types=1);

namespace App\Components\RegistrationForm;

use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use App\Services\RegistrationService;
use App\Models\FacilityManager;

class CompleteRegistrationControl extends Control
{
    /** @var array<callable(self): void> */
    public array $onComplete = [];

    public function __construct(
        private int $clinicId,
        private RegistrationService $registrationService,
        private FacilityManager $facilityManager
    ) {}

    protected function createComponentForm(): Form
    {
        $form = new Form;
        
        $form->addText('name', 'Název zařízení')
            ->setRequired('Zadejte název zařízení.');

        // Adresa
        $form->addText('address_street_number', 'Ulice, číslo popisné')
            ->setRequired('Zadejte ulici a číslo popisné.');
        $form->addText('address_city', 'Město')
            ->setRequired('Zadejte město.');
        $form->addText('address_ZIP', 'PSČ')
            ->setRequired('Zadejte PSČ.')
            ->addRule($form::Pattern, 'PSČ musí být ve formátu 12345', '^\d{5}$');
        
        $form->addText('web', 'Webové stránky')
            ->addRule($form::URL, 'Zadejte platnou URL adresu.');
        $form->addTextArea('description', 'Popis zařízení')
            ->setRequired('Zadejte popis zařízení.')
            ->setHtmlAttribute('rows', 5);

        $programs = $this->facilityManager->getProgramNames();

        $programType = $form->addRadioList('program_type', 'Varianta spolupráce', $programs)
            ->setRequired('Vyberte prosím variantu spolupráce.');

        // --- DYNAMICKÁ POLE ---

        $form->addText('reservation_email', 'Kontaktní e-mail')
            ->addConditionOn($programType, $form::IsIn, [1, 2]) //magicky cisla :(
                ->setRequired('Zadejte e-mail.')
                ->addRule($form::Email, 'Zadejte platný e-mail.')
                ->toggle('snippet-email'); // Toto ID musí být v Latte

        $form->addText('reservation_phone', 'Kontaktní telefon')
            ->addConditionOn($programType, $form::Equal, 1)
                ->setRequired('Zadejte telefon.')
                ->addRule($form::Pattern, 'Telefon musí být ve formátu +420123456789', '^\+420\d{9}$')
                ->toggle('snippet-phone');

        $form->addInteger('max_patients', 'Maximální počet pacientů', )
            ->addConditionOn($programType, $form::Equal, 2)
                ->setRequired('Zadejte limit.')
                ->addRule($form::Min, 'Počet pacientů musí být kladné číslo.', 1)
                ->toggle('snippet-patients');

        // --- KONEC DYNAMICKÝCH POLÍ ---

        $form->addCheckbox('tos', 'Souhlasím s podmínkami služby.')
            ->setRequired('Musíte souhlasit s podmínkami služby.');

        $form->addSubmit('send', 'Dokončit registraci');
        $form->onSuccess[] = [$this, 'processForm'];
        
        return $form;
    }

    public function processForm(Form $form, array $values): void
    {
        $this->registrationService->completeRegistration($this->clinicId, $values);
        $this->onComplete($this);
    }

    public function render(): void
    {
        $this->template->descriptions = $this->facilityManager->getProgramDescriptions();
        $this->template->setFile(__DIR__ . '/completeForm.latte');
        $this->template->render();
    }
}