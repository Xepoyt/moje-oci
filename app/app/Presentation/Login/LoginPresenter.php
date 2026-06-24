<?php declare(strict_types=1);

namespace App\Presentation\Login;

use App\Components\Account\ChangePasswordControl;
use App\Components\Account\ChangePasswordControlFactory;
use App\Components\Login\ForgottenPasswordControl;
use App\Components\Login\ForgottenPasswordControlFactory;
use Nette\Application\UI\Presenter;
use App\Components\Login\LoginControlFactory;
use App\Components\Login\LoginControl;
use App\Models\FacilityManager;
use Nette\Database\Table\ActiveRow;

final class LoginPresenter extends Presenter
{
    private ActiveRow $clinicRecord;

    public function __construct(
        private LoginControlFactory $loginControlFactory,
        private ChangePasswordControlFactory $changePasswordControlFactory,
        private ForgottenPasswordControlFactory $forgottenPasswordControlFactory,
        private FacilityManager $facilityManager
    ) {
        parent::__construct();
    }

    protected function createComponentLoginForm(): LoginControl
    {
        $control = $this->loginControlFactory->create();
        
        // Komponenta odpracovala přihlášení, my už jen přesměrujeme
        $control->onSuccess[] = function (LoginControl $control) {
            $this->redirect(':Account:Account:overview');
        };
        
        return $control;
    }

    protected function createComponentForgottenPasswordForm(): ForgottenPasswordControl
    {
        $control = $this->forgottenPasswordControlFactory->create();
        
        // Komponenta odpracovala přihlášení, my už jen přesměrujeme
        $control->onSuccess[] = function (ForgottenPasswordControl $control, \stdClass $values) {

            $clinic = $this->facilityManager->findByIco($values->ico);
            
            $this->redirect('Login:sentReset', ['email' => $clinic->email]);
        };
        
        return $control;
    }

    protected function createComponentPasswordForm(): ChangePasswordControl
    {
        $control = $this->changePasswordControlFactory->create($this->clinicRecord->id);

        $control->onComplete[] = function (ChangePasswordControl $control) {
            $this->flashMessage('Heslo bylo úspěšně změněno', 'success');
            $this->redirect(':Login:Login:default');
        };
        
        return $control;
    }

    public function actionReset(string $token): void
    {
        $this->clinicRecord = $this->facilityManager->findByToken($token);

        if (!$this->clinicRecord) {
            $this->redirect(':Home:Home:invalid');
        }
    }

    public function renderSentReset(string $email): void
    {
        $this->template->email = $email;
    }
}