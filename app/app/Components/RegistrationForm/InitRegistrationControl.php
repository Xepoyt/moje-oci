<?php

declare(strict_types=1);

namespace App\Components\RegistrationForm;

use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use App\Services\RegistrationService;
use Nette\Database\UniqueConstraintViolationException;
use App\Utils\IcoValidator;

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
            ->setRequired('IČO je povinné.')
            ->setHtmlAttribute('data-number-only', true)
            ->addRule($form::Pattern, 'IČO musí být ve formátu 12345678', '^(\s*\d){8}$')
            ->addRule('App\Utils\IcoValidator::validateNette', 'Zadané IČO není platné (neodpovídá kontrolní součet).');
        $form->addText('contact_person_name', 'Jméno')
            ->setRequired('Jméno je povinné.');
        $form->addText('contact_person_surname', 'Příjmení')
            ->setRequired('Příjmení je povinné.');
        $form->addText('email', 'E-mail')
            ->setRequired('E-mail je povinný.')
            ->addRule($form::Email, 'Zadejte platný e-mail.');
        $form->addPassword('password', 'Heslo')
            ->setRequired('Heslo je povinné.')
            ->addRule($form::MinLength, 'Heslo musí být dlouhé minimálně %d znaků.', 12);
        $form->addPassword('password_repeat', 'Zopakujte heslo')
            ->setRequired('Zopakování hesla je povinné.')
            ->addRule($form::Equal, 'Hesla se neshodují', $form['password']);
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
            
            $this->onComplete($this, $values);
            
        } catch (UniqueConstraintViolationException $e) {
            $form->addError('Toto IČO už u nás prošlo/prochází registrací.');
        }
    }
    
    public function render(): void
    {
        $this->template->setFile(__DIR__ . '/initForm.latte');
        $this->template->render();
    }
}