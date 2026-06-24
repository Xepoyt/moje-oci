<?php

declare(strict_types=1);

namespace App\Components\Account;

use App\Models\FacilityManager;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use App\Services\AccountService;
use Nette\Database\UniqueConstraintViolationException;
use App\Utils\IcoValidator;
use Nette\Security\User;

class ChangeContactControl extends Control
{
    /** @var array<callable(self): void> */
    public array $onComplete = [];

    private int $id;

    public function __construct(
        private AccountService $accountService,
        private FacilityManager $facilityManager,
        private User $user
    ) {
        $this->id = $this->user->getId();
    }

    protected function createComponentForm(): Form
    {
        $clinic = $this->facilityManager->getClinic($this->id);
        $form = new Form;
        $form->addText('contact_person_name', 'Jméno')
            ->setDefaultValue($clinic->contact_person_name);
        $form->addText('contact_person_surname', 'Příjmení')
            ->setDefaultValue($clinic->contact_person_surname);
        $form->addText('email', 'E-mail')
            ->setDefaultValue($clinic->email)
            ->addRule($form::Email, 'Zadejte platný e-mail.');
        $form->addSubmit('send', 'Uložit');

        $form->onSuccess[] = [$this, 'processForm'];
        return $form;
    }

    public function processForm(Form $form, \stdClass $values): void
    {
        $this->accountService->changeContact($values, $this->id);
        
        $this->onComplete($this, $values);
    }
    
    public function render(): void
    {
        $this->template->setFile(__DIR__ . '/contactForm.latte');
        $this->template->render();
    }
}