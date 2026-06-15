<?php

declare(strict_types=1);

namespace App\Presentation\Registration;

use Nette\Application\UI\Presenter;
use App\Models\FacilityManager;
use App\Components\RegistrationForm\InitRegistrationControl;
use App\Components\RegistrationForm\CompleteRegistrationControl;
use App\Components\RegistrationForm\InitRegistrationControlFactory;
use App\Components\RegistrationForm\CompleteRegistrationControlFactory;

class RegistrationPresenter extends Presenter
{
    private ?\Nette\Database\Table\ActiveRow $clinicRecord = null;

    public function __construct(
        // Už nepotřebujeme EmailService, ten si bere komponenta
        private FacilityManager $facilityManager,
        
        // Využijeme automaticky generované továrničky (viz bod 3 níže)
        private InitRegistrationControlFactory $initControlFactory,
        private CompleteRegistrationControlFactory $completeControlFactory
    ) {
        parent::__construct();
    }

    // --- KROK 1 ---
    protected function createComponentInitForm(): InitRegistrationControl
    {
        // Továrnička nám vytvoří komponentu s už doplněnými závislostmi
        $control = $this->initControlFactory->create();
        
        // Nastavíme, co se má stát po úspěšném zpracování (Routing + Flash)
        $control->onComplete[] = function () {
            $this->flashMessage('Na váš e-mail byl odeslán odkaz pro pokračování.', 'success');
            $this->redirect('this');
        };

        return $control;
    }

    // --- KROK 2 ---
    public function actionComplete(string $token): void
    {
        $this->clinicRecord = $this->facilityManager->findByToken($token);

        if (!$this->clinicRecord) {
            $this->flashMessage('Odkaz je neplatný nebo již byl použit.', 'error');
            $this->redirect('Registration:default');
        }
    }

    protected function createComponentCompleteForm(): CompleteRegistrationControl
    {
        $control = $this->completeControlFactory->create($this->clinicRecord->id);
        
        $control->onComplete[] = function () {
            $this->flashMessage('Registrace byla úspěšně dokončena! Vyčkejte na schválení administrátorem.', 'success');
            $this->redirect('Registration:default');
        };

        return $control;
    }
}