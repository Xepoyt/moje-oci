<?php

declare(strict_types=1);

namespace App\Components\RegistrationForm;

use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use App\Services\RegistrationService;

class CompleteRegistrationControl extends Control
{
    /** @var array<callable(self): void> */
    public array $onComplete = [];

    public function __construct(
        private int $clinicId,
        private RegistrationService $registrationService
    ) {}

    protected function createComponentForm(): Form
    {
        $form = new Form;
        
        $form->addText('name', 'Název zařízení')
            ->setRequired('Zadejte název zařízení.');
        $form->addText('address', 'Adresa')
            ->setRequired('Zadejte adresu.'); //TODO: rozdělit adresu na ulici + č.p., město, PSČ
        $form->addText('web', 'Webové stránky')
            ->addRule($form::URL, 'Zadejte platnou URL adresu.');
        $form->addTextArea('description', 'Popis zařízení')
            ->setRequired('Zadejte popis zařízení.')
            ->setHtmlAttribute('rows', 5);

        $programs = [ //TODO: upravit nazvy
            1 => 'Kontaktní údaje',
            2 => '"Ozveme se"',
            3 => 'Rezervační systém (API)'
        ];

        $programType = $form->addRadioList('program_type', 'Varianta spolupráce', $programs)
            ->setRequired('Vyberte prosím variantu spolupráce.'); //TODO: přidat popisky k jednotlivým variantám, aby bylo jasné, co znamenají

        // --- DYNAMICKÁ POLE ---

        $form->addEmail('reservation_email', 'Kontaktní e-mail')
            ->addConditionOn($programType, $form::IsIn, [1, 2])
                ->setRequired('Zadejte e-mail.')
                ->addRule($form::Email, 'Zadejte platný e-mail.')
                ->toggle('snippet-email'); // Toto ID musí být v Latte

        $form->addText('reservation_phone', 'Kontaktní telefon')
            ->addConditionOn($programType, $form::Equal, 1)
                ->setRequired('Zadejte telefon.')
                ->addRule($form::Pattern, 'Telefon musí být ve formátu +420 123 456 789', '^\+420\s\d{3}\s\d{3}\s\d{3}$')
                ->toggle('snippet-phone');

        $form->addInteger('max_patients', 'Maximální počet pacientů', )
            ->addConditionOn($programType, $form::Equal, 2)
                ->setRequired('Zadejte limit.')
                ->addRule($form::Min, 'Počet pacientů musí být kladné číslo.', 1)
                ->toggle('snippet-patients');

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
        $this->template->setFile(__DIR__ . '/completeForm.latte');
        $this->template->render();
    }
}