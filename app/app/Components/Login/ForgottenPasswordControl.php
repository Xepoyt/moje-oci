<?php declare(strict_types=1);

namespace App\Components\Login;

use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Nette\Security\AuthenticationException;
use App\Utils\IcoValidator;
use App\Services\AccountService;
use App\Exceptions\ClinicNotFoundException;

class ForgottenPasswordControl extends Control
{
    /** @var array<callable(self): void> */
    public array $onSuccess = [];

    public function __construct(
        private AccountService $accountService
    ) {
    }

    protected function createComponentForm(): Form
    {
        $form = new Form;
        
        $form->addText('ico', 'IČO')
            ->setRequired('IČO je povinné.')
            ->setHtmlAttribute('data-number-only', true)
            ->addRule($form::Pattern, 'IČO musí být ve formátu 12345678', '^(\s*\d){8}$')
            ->addRule([IcoValidator::class, 'validateNette'], 'Zadané IČO není platné.');

        $form->addSubmit('send', 'Odeslat');

        $form->onSuccess[] = [$this, 'processForm'];
        return $form;
    }

    public function processForm(Form $form, \stdClass $values): void
    {
        try {
            $this->accountService->resetPassword($values->ico);
            
            // Oznámíme rodiči (Presenter), že je hotovo
            $this->onSuccess($this, $values);
            
        } catch (ClinicNotFoundException $e) {
            $form->addError($e->getMessage());
        }
    }

    public function render(): void
    {
        $this->template->setFile(__DIR__ . '/forgottenPasswordForm.latte');
        $this->template->render();
    }
}