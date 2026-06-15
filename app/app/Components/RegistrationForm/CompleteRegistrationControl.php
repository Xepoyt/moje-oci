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
        
        $form->addText('name', 'Název zařízení')->setRequired('Zadejte název zařízení.');
        $form->addText('address', 'Adresa')->setRequired('Zadejte adresu.');
        $form->addText('web', 'Webové stránky');
        $form->addTextArea('description', 'Popis zařízení');

        $programs = [
            1 => 'Kontaktní údaje',
            2 => '"Ozveme se"',
            3 => 'Rezervační systém (API)'
        ];

        $programType = $form->addRadioList('program_type', 'Varianta spolupráce', $programs)
            ->setRequired('Vyberte prosím variantu spolupráce.');

        // --- DYNAMICKÁ POLE ---

        $form->addEmail('reservation_email', 'Kontaktní e-mail')
            ->addConditionOn($programType, $form::IsIn, [1, 2])
                ->setRequired('Zadejte e-mail.')
                ->toggle('snippet-email'); // Toto ID musí být v Latte

        $form->addText('reservation_phone', 'Kontaktní telefon')
            ->addConditionOn($programType, $form::Equal, 1)
                ->setRequired('Zadejte telefon.')
                ->toggle('snippet-phone');

        $form->addInteger('max_patients', 'Maximální počet pacientů')
            ->addConditionOn($programType, $form::Equal, 2)
                ->setRequired('Zadejte limit.')
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