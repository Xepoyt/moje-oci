<?php

declare(strict_types=1);

namespace App\Components\Account;

use App\Models\FacilityManager;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Nette\Database\UniqueConstraintViolationException;
use Nette\Security\User;

class ChangePasswordControl extends Control
{
    /** @var array<callable(self): void> */
    public array $onComplete = [];

    public function __construct(
        private int $clinicId,
        private FacilityManager $facilityManager
    ) {
    }

    protected function createComponentForm(): Form
    {
        $form = new Form;
        $form->addPassword('password', 'Heslo')
            ->setRequired('Pokud chcete heslo změnit, vyplňte ho.')
            ->addRule($form::MinLength, 'Heslo musí být dlouhé minimálně %d znaků.', 12);
        $form->addPassword('password_repeat', 'Zopakujte heslo')
            ->setRequired('Zopakování hesla je povinné.')
            ->addRule($form::Equal, 'Hesla se neshodují', $form['password']);
        $form->addSubmit('send', 'Uložit');

        $form->onSuccess[] = [$this, 'processForm'];
        return $form;
    }

    public function processForm(Form $form, \stdClass $values): void
    {
        $this->facilityManager->changePassword($this->clinicId, $values->password);
        
        $this->onComplete($this, $values);
    }
    
    public function render(): void
    {
        $this->template->setFile(__DIR__ . '/passwordForm.latte');
        $this->template->render();
    }
}