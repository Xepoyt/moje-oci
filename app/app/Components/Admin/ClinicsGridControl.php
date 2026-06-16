<?php

declare(strict_types=1);

namespace App\Components\Admin;

use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use App\Models\FacilityManager;
use App\Services\EmailService;
use Nette\Utils\Paginator;
use App\Services\RegistrationService;
use Nette\Forms\Controls\SubmitButton;
use Nette\Application\Attributes\Persistent;

class ClinicsGridControl extends Control
{
    #[Persistent]
    /** @persistent */
    public int $page = 1;

    #[Persistent]
    /** @persistent */
    public string $sort = 'created_at';

    #[Persistent]
    /** @persistent */
    public string $order = 'DESC';

    #[Persistent]
    /** @persistent */
    public ?string $searchField = null;

    #[Persistent]
    /** @persistent */
    public ?string $searchQuery = null;

    private int $itemsPerPage = 5;

    public function __construct(
        private FacilityManager $facilityManager,
        private RegistrationService $registrationService
    ) {}

    public function handleRedraw(): void
    {
        if ($this->getPresenter()->isAjax()) {
            $this->redrawControl('grid');
        } else {
            $this->redirect('this');
        }
    }

    public function render(): void
    {
        $allowedSorts = ['name', 'contact_person', 'address', 'program_type', 'is_approved', 'created_at'];
        if (!in_array($this->sort, $allowedSorts, true)) {
            $this->sort = 'created_at';
        }
        $this->order = strtoupper($this->order) === 'ASC' ? 'ASC' : 'DESC';

        $paginator = new Paginator();
        $paginator->setItemCount($this->facilityManager->getClinicsCount($this->searchField, $this->searchQuery));
        $paginator->setItemsPerPage($this->itemsPerPage);
        $paginator->setPage($this->page);

        $this->template->clinics = $this->facilityManager->getClinicsPage(
            $paginator->getOffset(), 
            $paginator->getLength(),
            $this->sort,
            $this->order,
            $this->searchField,
            $this->searchQuery
        );

        $this->template->programs = $this->facilityManager->getProgramNames();
        
        $this->template->paginator = $paginator;
        $this->template->sort = $this->sort;
        $this->template->order = $this->order;
        
        
        
        $this->template->setFile(__DIR__ . '/clinicsGrid.latte');
        $this->template->render();
    }

    protected function createComponentSearchForm(): Form
    {
        $form = new Form;
        
        $fields = [
            'name' => 'Název ordinace',
            'contact_person_name' => 'Jméno kontaktní osoby',
            'contact_person_surname' => 'Příjmení kontaktní osoby',
            'email' => 'E-mail',
            'address_street_number' => 'Ulice a č.p.',
            'address_city' => 'Město',
            'address_ZIP' => 'PSČ',
        ];
        
        $form->addSelect('field', 'Hledat v:', $fields)
             ->setDefaultValue($this->searchField);
             
        $form->addText('query', 'Hledaný text')
             ->setDefaultValue($this->searchQuery);
             
        $form->addSubmit('search', 'Vyhledat')
             ->onClick[] = [$this, 'searchFormSucceeded'];

        $form->addSubmit('reset', 'Zrušit filter')
             ->setValidationScope([]) 
             ->onClick[] = [$this, 'searchFormReset'];
             
        
        return $form;
    }

    public function searchFormSucceeded(SubmitButton $button): void
    {
        $values = $button->getForm()->getValues();

        $this->searchField = $values->field;
        $this->searchQuery = $values->query;
        $this->page = 1; 

        if ($this->getPresenter()->isAjax()) {
            $this->redrawControl('grid');
            $this->getPresenter()->payload->postGet = true;
            $this->getPresenter()->payload->url = $this->link('this');
        } else {
            $this->redirect('this');
        }
    }

    public function searchFormReset(): void
    {
        $this->searchField = null;
        $this->searchQuery = null;
        $this->page = 1;
        
        $this->getComponent('searchForm')->setValues([], true);

        if ($this->getPresenter()->isAjax()) {
            $this->redrawControl('grid');
            $this->getPresenter()->payload->postGet = true;
            $this->getPresenter()->payload->url = $this->link('this');
        } else {
            $this->redirect('this');
        }
    }

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

        if ($this->getPresenter()->isAjax()) {
            $this->redrawControl('grid');
            $this->getPresenter()->payload->url = $this->link('this');
            $this->getPresenter()->redrawControl('flashes'); 
        } else {
            $this->redirect('this');
        }
    }
}