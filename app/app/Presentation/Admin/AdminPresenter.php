<?php

declare(strict_types=1);

namespace App\Presentation\Admin;

use Nette\Application\UI\Presenter;
use App\Components\Admin\ClinicsGridControlFactory;
use App\Components\Admin\ClinicsGridControl;

class AdminPresenter extends Presenter
{
    public function __construct(
        private ClinicsGridControlFactory $gridFactory
    ) {
        parent::__construct();
    }

    protected function createComponentClinicsGrid(): ClinicsGridControl
    {
        return $this->gridFactory->create();
    }
}