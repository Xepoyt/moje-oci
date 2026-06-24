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


class ChangeClinicControl extends Control
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
        
        $form->addText('name', 'Název zařízení')
            ->setDefaultValue($clinic->name);

        // Adresa
        $form->addText('address_street_number', 'Ulice, číslo popisné')
            ->setDefaultValue($clinic->address_street_number);
        $form->addText('address_city', 'Město')
            ->setDefaultValue($clinic->address_city);
        $form->addText('address_ZIP', 'PSČ')
            ->setHtmlAttribute('data-number-only', true)
            ->setDefaultValue($clinic->address_ZIP)
            ->addRule($form::Pattern, 'PSČ musí být ve formátu 12345', '^(\s*\d){5}$');
        
        $form->addText('web', 'Webové stránky')
            ->setDefaultValue($clinic->web)
            ->addRule($form::URL, 'Zadejte platnou URL adresu.');
        $form->addTextArea('description', 'Popis zařízení')
            ->setDefaultValue($clinic->description)
            ->setHtmlAttribute('rows', 5);

        $programs = $this->facilityManager->getProgramNames();

        $programType = $form->addRadioList('program_type', 'Varianta spolupráce', $programs)
            ->setDefaultValue($clinic->program_type);

        // --- DYNAMICKÁ POLE ---

        $form->addText('reservation_email', 'Kontaktní e-mail')
            ->setDefaultValue($clinic->reservation_email)
            ->addConditionOn($programType, $form::IsIn, [1, 2]) //magicky cisla :(
                ->setRequired('Zadejte e-mail.')
                ->addRule($form::Email, 'Zadejte platný e-mail.')
                ->toggle('snippet-email'); // Toto ID musí být v Latte

        $form->addText('reservation_phone', 'Kontaktní telefon')
            ->setHtmlAttribute('data-phone-only', true)
            ->setDefaultValue($clinic->reservation_phone)
            ->addConditionOn($programType, $form::Equal, 1)
                ->setRequired('Zadejte telefon.')
                ->addRule($form::Pattern, 'Telefon musí být ve formátu +420123456789', '^\+420(\s*\d){9}$')
                ->toggle('snippet-phone');

        $form->addInteger('max_patients', 'Maximální počet pacientů', )
            ->setDefaultValue($clinic->max_patients)
            ->addConditionOn($programType, $form::Equal, 2)
                ->setRequired('Zadejte limit.')
                ->addRule($form::Min, 'Počet pacientů musí být kladné číslo.', 1)
                ->toggle('snippet-patients');

        // --- KONEC DYNAMICKÝCH POLÍ ---

        $form->addSubmit('send', 'Uložit');
        $form->onSuccess[] = [$this, 'processForm'];
        
        return $form;
    }

    public function processForm(Form $form, \stdClass $values): void
    {
        $this->accountService->changeClinic($values, $this->id);
        $this->onComplete($this, $values);
    }

    public function render(): void
    {
        $this->template->descriptions = $this->facilityManager->getProgramDescriptions();
        $this->template->setFile(__DIR__ . '/clinicForm.latte');
        $this->template->render();
    }
}