<?php

declare(strict_types=1);

namespace App\Presentation\Registration;

use Nette\Application\UI\Presenter;
use App\Models\FacilityManager;
use App\Components\RegistrationForm\InitRegistrationControl;
use App\Components\RegistrationForm\CompleteRegistrationControl;
use App\Components\RegistrationForm\InitRegistrationControlFactory;
use App\Components\RegistrationForm\CompleteRegistrationControlFactory;
use App\Components\Info\ClinicDetailControl;
use App\Components\Info\ClinicDetailControlFactory;

class RegistrationPresenter extends Presenter
{
    private ?\Nette\Database\Table\ActiveRow $clinicRecord = null;

    public function __construct(
        private FacilityManager $facilityManager,
        private InitRegistrationControlFactory $initControlFactory,
        private CompleteRegistrationControlFactory $completeControlFactory,
        private ClinicDetailControlFactory $clinicDetailControlFactory
    ) {
        parent::__construct();
    }

    protected function createComponentInitForm(): InitRegistrationControl
    {
        $control = $this->initControlFactory->create();
        
        $control->onComplete[] = function (InitRegistrationControl $control, \stdClass $values) {
            $this->flashMessage('Na váš e-mail byl odeslán odkaz pro pokračování.', 'success');
            $this->redirect('Registration:sent', ['email' => $values->email]);
        };

        return $control;
    }

    public function actionComplete(string $token): void
    {
        $this->clinicRecord = $this->facilityManager->findByToken($token);

        if (!$this->clinicRecord) {
            $this->flashMessage('Odkaz je neplatný nebo již byl použit. Pokud jste registraci již dokončili, přihlaste se.', 'danger');
            $this->redirect(':Home:Home:default');
        }
    }

    protected function createComponentCompleteForm(): CompleteRegistrationControl
    {
        $control = $this->completeControlFactory->create($this->clinicRecord->id);
        
        $control->onComplete[] = function () {
            $this->flashMessage('Registrace byla úspěšně dokončena! Vyčkejte na schválení administrátorem.', 'success');
            $this->redirect('Registration:summary', ['id' => $this->clinicRecord->id]);
        };

        return $control;
    }

    public function renderSummary(int $id): void
    {
        $this->template->found = true;
        // Ochrana: Pokud někdo zkusí zadat neexistující ID rovnou do URL
        $userData = $this->facilityManager->getClinic($id);
        if (!$userData) {
            $this->template->found = false;
            $this->flashMessage('Zadané ID neexistuje.', 'danger');
        }

        // Předáme data do Latte šablony
        $this->template->clinicId = $id;
    }

    public function renderSent(string $email): void
    {
        $this->template->email = $email;
    }

    public function createComponentClinicDetail(): ClinicDetailControl
    {
        return $this->clinicDetailControlFactory->create();
    }
}