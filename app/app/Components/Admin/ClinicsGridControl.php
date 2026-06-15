<?php

declare(strict_types=1);

namespace App\Components\Admin;

use Nette\Application\UI\Control;
use App\Models\FacilityManager;
use App\Services\EmailService;
use Nette\Utils\Paginator;
use App\Services\RegistrationService;

class ClinicsGridControl extends Control
{
    /** @persistent */
    public int $page = 1;

    /** @persistent */
    public string $sort = 'created_at'; // Výchozí sloupec pro řazení

    /** @persistent */
    public string $order = 'DESC'; // Výchozí směr řazení

    private int $itemsPerPage = 10;

    public function __construct(
        private FacilityManager $facilityManager,
        private EmailService $emailService,
        private RegistrationService $registrationService
    ) {}

    public function render(): void
    {
        $allowedSorts = ['name', 'contact_person', 'address', 'program_type', 'is_approved', 'created_at'];
        if (!in_array($this->sort, $allowedSorts, true)) {
            $this->sort = 'created_at';
        }
        $this->order = strtoupper($this->order) === 'ASC' ? 'ASC' : 'DESC';

        $paginator = new Paginator();
        $paginator->setItemCount($this->facilityManager->getClinicsCount());
        $paginator->setItemsPerPage($this->itemsPerPage);
        $paginator->setPage($this->page);

        $this->template->clinics = $this->facilityManager->getClinicsPage(
            $paginator->getOffset(), 
            $paginator->getLength(),
            $this->sort,
            $this->order
        );
        
        $this->template->paginator = $paginator;
        $this->template->sort = $this->sort;
        $this->template->order = $this->order;
        
        $this->template->setFile(__DIR__ . '/clinicsGrid.latte');
        $this->template->render();
    }

    /**
     * Signál pro schválení, který se teď volá uvnitř komponenty
     */
    public function handleApprove(int $id): void
    {
        $clinic = $this->facilityManager->getClinic($id);
        
        if ($clinic) {
            $this->facilityManager->approveClinic($id);
            $this->registrationService->approveClinic($id);
            
            // Komponenty mohou vyhazovat flash messages přímo přes svůj presenter
            $this->getPresenter()->flashMessage('Klinika byla úspěšně schválena.', 'success');
        } else {
            $this->getPresenter()->flashMessage('Klinika nebyla nalezena.', 'error');
        }

        // Přesměrování na "tuto stránku" zamezí opakování akce při obnovení prohlížeče (F5)
        $this->redirect('this');
    }
}