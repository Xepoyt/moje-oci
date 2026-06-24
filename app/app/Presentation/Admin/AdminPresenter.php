<?php declare(strict_types=1);

namespace App\Presentation\Admin;

use Nette\Application\UI\Presenter;
use Nette\Application\UI\Form;
use Nette\Application\Attributes\Persistent;
use App\Components\Admin\ClinicsGridControlFactory;
use App\Components\Admin\ClinicsGridControl;
use App\Components\Info\ChangeRequestDetailControl;
use App\Components\Info\ChangeRequestDetailControlFactory;
use App\Components\Info\ClinicDetailControlFactory;
use App\Components\Info\ClinicDetailControl;
use App\Services\RegistrationService;
use App\Models\FacilityManager;

class AdminPresenter extends Presenter
{
    #[Persistent]
    public ?int $detailId = null;

    public function __construct(
        private ClinicsGridControlFactory $gridFactory,
        private ClinicDetailControlFactory $detailFactory,
        private ChangeRequestDetailControlFactory $changeRequestDetailFactory,
        private RegistrationService $registrationService,
        private FacilityManager $facilityManager
    ) {
        parent::__construct();
    }

    // Volá se po kliknutí na řádek v datagridu
    public function handleShowDetail(int $id): void
    {
        $this->detailId = $id;
        if ($this->isAjax()) {
            $this->redrawControl('modalSnippet');
            $this->payload->showModal = true; 
        } else {
            $this->redirect('this');
        }
    }

    // Akce přímo v modálu
    public function handleApproveDetail(): void
    {
        if ($this->detailId) {
            $this->registrationService->approveClinic($this->detailId);
            $this->flashMessage('Klinika byla úspěšně schválena.', 'success');
            
            if ($this->isAjax()) {
                $this->redrawControl('flashes');
                $this->redrawControl('modalSnippet');
                $this->getComponent('clinicsGrid')->redrawControl('grid');
            } else {
                $this->redirect('this');
            }
        }
    }

    // Formulář pro zamítnutí uvnitř modálu
    protected function createComponentDenyForm(): Form
    {
        $form = new Form;
        $form->addTextArea('reason', 'Důvod zamítnutí / zrušení schválení:')
            ->setRequired('Vyplňte důvod zamítnutí, tento text bude zaslán uživateli.');
            
        $form->addSubmit('send', 'Odeslat rozhodnutí');
        
        $form->onSuccess[] = function (Form $form, \stdClass $values) {
            if ($this->detailId) {
                $this->registrationService->denyClinic($this->detailId, $values->reason);
                $this->flashMessage('Klinika byla zamítnuta.', 'warning');
                
                if ($this->isAjax()) {
                    $this->redrawControl('flashes');
                    $this->redrawControl('modalSnippet');
                    $this->getComponent('clinicsGrid')->redrawControl('grid');
                } else {
                    $this->redirect('this');
                }
            }
        };
        return $form;
    }

    public function renderDefault(): void
    {
        // Předáme do šablony záznam kliniky, abychom znali její is_approved stav pro tlačítka
        $this->template->detailClinic = $this->detailId ? $this->facilityManager->getClinic($this->detailId) : null;
        $this->template->detailId = $this->detailId;
    }

    protected function createComponentClinicsGrid(): ClinicsGridControl
    {
        return $this->gridFactory->create();
    }

    protected function createComponentClinicDetail(): ClinicDetailControl
    {
        return $this->detailFactory->create();
    }

    protected function createComponentChangeRequestDetail(): ChangeRequestDetailControl
    {
        return $this->changeRequestDetailFactory->create();
    }
}