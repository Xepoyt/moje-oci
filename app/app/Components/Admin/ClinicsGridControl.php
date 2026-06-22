<?php declare(strict_types=1);

namespace App\Components\Admin;

use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use App\Models\FacilityManager;
use Nette\Utils\Paginator;
use Nette\Application\Attributes\Persistent;

class ClinicsGridControl extends Control
{
    #[Persistent] public int $page = 1;
    #[Persistent] public string $sort = 'created_at';
    #[Persistent] public string $order = 'DESC';

    // Per-sloupec filtry
    #[Persistent] public ?string $fIco = null;
    #[Persistent] public ?string $fName = null;
    #[Persistent] public ?string $fEmail = null;
    #[Persistent] public ?int $fProgram = null;
    #[Persistent] public ?int $fStatus = null;

    private int $itemsPerPage = 10;

    public function __construct(private FacilityManager $facilityManager) {}

    public function handleRedraw(): void
    {
        $this->getPresenter()->isAjax() ? $this->redrawControl('grid') : $this->redirect('this');
    }

    public function render(): void
    {
        $filters = [
            'ico' => $this->fIco, 'name' => $this->fName, 'email' => $this->fEmail,
            'program_type' => $this->fProgram, 'status' => $this->fStatus
        ];

        $paginator = new Paginator();
        $paginator->setItemCount($this->facilityManager->getClinicsCount($filters));
        $paginator->setItemsPerPage($this->itemsPerPage);
        $paginator->setPage($this->page);

        $this->template->clinics = $this->facilityManager->getClinicsPage(
            $paginator->getOffset(), $paginator->getLength(), $this->sort, $this->order, $filters
        );

        $this->template->programs = $this->facilityManager->getProgramNames();
        $this->template->paginator = $paginator;
        $this->template->sort = $this->sort;
        $this->template->order = $this->order;
        
        $this->template->render(__DIR__ . '/clinicsGrid.latte');
    }

    protected function createComponentFilterForm(): Form
    {
        $form = new Form;
        $form->addText('ico')->setDefaultValue($this->fIco);
        $form->addText('name')->setDefaultValue($this->fName);
        $form->addText('email')->setDefaultValue($this->fEmail);
        $form->addSelect('program_type', null, [null => 'Vše'] + $this->facilityManager->getProgramNames())
             ->setDefaultValue($this->fProgram);
        $form->addSelect('status', null, [
            null => 'Vše',
            4 => 'Čeká na schválení', 1 => 'Schváleno',
            2 => 'Žádá o změnu', 3 => 'Zamítnuto', 0 => 'Neověřeno'
        ])->setDefaultValue($this->fStatus);
        
        $form->addSubmit('filter', 'Filtrovat')->onClick[] = [$this, 'processFilter'];
        $form->addSubmit('reset', 'Zrušit')->setValidationScope([])->onClick[] = [$this, 'resetFilter'];
        return $form;
    }

    public function processFilter($button): void
    {
        $values = $button->getForm()->getValues();
        
        $this->fIco = $values->ico ?: null;
        $this->fName = $values->name ?: null;
        $this->fEmail = $values->email ?: null;
        
        //Ošetříme prázdný řetězec, který posílá volba "Vše"
        $this->fProgram = ($values->program_type === '' || $values->program_type === null) 
            ? null 
            : (int)$values->program_type;
            
        $this->fStatus = ($values->status === '' || $values->status === null) 
            ? null 
            : (int)$values->status;
            
        $this->page = 1;
        $this->getPresenter()->isAjax() ? $this->redrawControl('grid') : $this->redirect('this');
    }

    public function resetFilter(): void
    {
        $this->fIco = $this->fName = $this->fEmail = $this->fProgram = $this->fStatus = null;
        $this->page = 1;
        $this->getComponent('filterForm')->setValues([], true);
        $this->getPresenter()->isAjax() ? $this->redrawControl('grid') : $this->redirect('this');
    }

    public function handlePage(int $page): void
    {
        $this->page = $page;
        
        if ($this->getPresenter()->isAjax()) {
            $this->redrawControl('grid');
        } else {
            $this->redirect('this');
        }
    }
}