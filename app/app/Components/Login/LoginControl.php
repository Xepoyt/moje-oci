<?php declare(strict_types=1);

namespace App\Components\Login;

use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Nette\Security\User;
use Nette\Security\AuthenticationException;
use App\Utils\IcoValidator;
use App\Security\ClinicAuthenticator;

class LoginControl extends Control
{
    /** @var array<callable(self): void> */
    public array $onSuccess = [];

    public function __construct(
        private User $user,
        private ClinicAuthenticator $clinicAuthenticator
    ) {
        $this->user->authenticator = $this->clinicAuthenticator;
    }

    protected function createComponentForm(): Form
    {
        $form = new Form;
        
        $form->addText('ico', 'IČO')
            ->setRequired('IČO je povinné.')
            ->setHtmlAttribute('data-number-only', true)
            ->addRule($form::Pattern, 'IČO musí být ve formátu 12345678', '^(\s*\d){8}$')
            ->addRule([IcoValidator::class, 'validateNette'], 'Zadané IČO není platné.');

        $form->addPassword('password', 'Heslo')
            ->setRequired('Heslo je povinné.');

        $form->addSubmit('send', 'Přihlásit se');

        $form->onSuccess[] = [$this, 'processForm'];
        return $form;
    }

    public function processForm(Form $form, \stdClass $values): void
    {
        try {
            // Komponenta se postará o ověření a zalogování
            $this->user->login($values->ico, $values->password);
            
            // Oznámíme rodiči (Presenter), že je hotovo
            $this->onSuccess($this);
            
        } catch (AuthenticationException $e) {
            $form->addError($e->getMessage());
        }
    }

    public function render(): void
    {
        $this->template->setFile(__DIR__ . '/loginForm.latte');
        $this->template->render();
    }
}