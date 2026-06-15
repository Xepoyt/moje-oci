<?php

declare(strict_types=1);

namespace App\Components\RegistrationForm;

use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use App\Services\RegistrationService;
use Nette\Database\UniqueConstraintViolationException;

class InitRegistrationControl extends Control
{
    /** @var array<callable(self): void> */
    public array $onComplete = [];

    public function __construct(
        private RegistrationService $registrationService
    ) {}

    protected function createComponentForm(): Form
    {
        $form = new Form;
        $form->addText('ico', 'IČO')
            ->setRequired()
            ->addRule($form::Pattern, 'IČO musí být ve formátu 8 číslic', '^[0-9]{8}$');
        $form->addText('contact_person', 'Kontaktní osoba')
            ->setRequired('Jméno kontaktní osoby je povinné.'); //TODO: rozdělit jméno a příjmení
        $form->addEmail('email', 'E-mail')
            ->setRequired('E-mail je povinný.')
            ->addRule($form::Email, 'Zadejte platný e-mail.');
        $form->addCheckbox('is_authorized', 'Jsem oprávněný jednat za toto zařízení')
            ->setRequired('Musíte potvdit oprávnění.');
        $form->addSubmit('send', 'Odeslat a ověřit e-mail');

        $form->onSuccess[] = [$this, 'processForm'];
        return $form;
    }

    public function processForm(Form $form, \stdClass $values): void
    {
        try {
            $this->registrationService->initiateRegistration($values);
            
            $this->onComplete($this);
            
        } catch (UniqueConstraintViolationException $e) {
            $form->addError('Toto IČO už u nás prochází/prošlo registrací.');
        }
    }
    
    public function render(): void
    {
        $this->template->setFile(__DIR__ . '/initForm.latte');
        $this->template->render();
    }
}