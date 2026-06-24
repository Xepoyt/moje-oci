<?php declare(strict_types=1);

namespace App\Presentation\Account;

use App\Components\Account\ChangeClinicControl;
use App\Components\Account\ChangeClinicControlFactory;
use App\Components\Account\ChangeContactControl;
use App\Components\Account\ChangeContactControlFactory;
use App\Components\Account\ChangePasswordControl;
use App\Components\Account\ChangePasswordControlFactory;
use Nette\Application\UI\Presenter;
use App\Components\Info\ClinicDetailControl;
use App\Components\Info\ClinicDetailControlFactory;
use App\Models\FacilityManager;
use App\Services\AccountService;

final class AccountPresenter extends Presenter
{
    public function __construct(
        private ClinicDetailControlFactory $clinicDetailControlFactory,
        private ChangeContactControlFactory $changeContactControlFactory,
        private ChangePasswordControlFactory $changePasswordControlFactory,
        private ChangeClinicControlFactory $changeClinicControlFactory,
        private FacilityManager $facilityManger,
        private AccountService $accountService
    ) {
        parent::__construct();
    }

    public function startup(): void
    {
        parent::startup();
        // Ochrana: Nepřihlášeného uživatele vykopneme zpět na login
        if (!$this->getUser()->isLoggedIn()) {
            $this->redirect(':Login:Login:default');
        }
    }

    public function renderOverview(): void
    {
        // Předáme ID přihlášené kliniky do šablony (pro vykreslení komponenty)
        $this->template->clinic = $this->facilityManger->getClinic($this->getUser()->id);
    }

    public function handleLogout(): void
    {
        $this->getUser()->logout();
        $this->flashMessage('Byli jste úspěšně odhlášeni.', 'success');
        $this->redirect(':Home:Home:default');
    }

    public function handleZaslatEmail(): void
    {
        $this->accountService->resendEmail($this->getUser()->id);
        $this->flashMessage('Nový odkaz byl odeslán.', 'success');
        $this->redirect('Account:overview');
    }

    protected function createComponentClinicDetail(): ClinicDetailControl
    {
        return $this->clinicDetailControlFactory->create();
    }

    protected function createComponentContactForm() : ChangeContactControl
    {
        $control = $this->changeContactControlFactory->create();

        $control->onComplete[] = function (ChangeContactControl $control, \stdClass $values) {
            $this->flashMessage('Vaše údaje byly úspěšně změněny.', 'success');
            $this->redirect('Account:overview');
        };

        return $control;
    }

    protected function createComponentClinicForm(): ChangeClinicControl
    {
        $control = $this->changeClinicControlFactory->create();

        $control->onComplete[] = function (ChangeClinicControl $control, \stdClass $values) {
            $this->flashMessage('Administrátorovi byla odeslána žádost o změnu údajů.', 'success');
            $this->redirect('Account:overview');
        };

        return $control;
    }

    protected function createComponentPasswordForm(): ChangePasswordControl
    {
        $control = $this->changePasswordControlFactory->create($this->getUser()->getId());

        $control->onComplete[] = function (ChangePasswordControl $control, \stdClass $values) {
            $this->flashMessage('Vaše heslo bylo úspěšně změněno.', 'success');
            $this->redirect('Account:overview');
        };

        return $control;
    }
}