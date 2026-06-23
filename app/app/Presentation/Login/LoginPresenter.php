<?php declare(strict_types=1);

namespace App\Presentation\Login;

use Nette\Application\UI\Presenter;
use App\Components\Login\LoginControlFactory;
use App\Components\Login\LoginControl;

final class LoginPresenter extends Presenter
{
    public function __construct(
        private LoginControlFactory $loginControlFactory
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
}