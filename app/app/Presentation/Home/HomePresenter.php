<?php declare(strict_types=1);

namespace App\Presentation\Home;

use Nette;
use App\Models\FacilityManager;


final class HomePresenter extends Nette\Application\UI\Presenter
{
    public function __construct(private FacilityManager $facilityManager) {
        parent::__construct();
    }
    public function renderDefault(): void
    {
        $this->template->programs = $this->facilityManager->getPrograms();
    }
    public function actionVerified(string $token): void
    {
        $clinic = $this->facilityManager->findByToken($token);

        if (!$clinic) {
            $this->redirect(':Home:Home:invalid');
        }
        elseif ($clinic->is_email_verified == 0){
            $this->redirect(':Registrations:Registration:complete', ['token' => $token]);
        }
        else{
            $this->facilityManager->verifyEmail($clinic->id);
        }
    }
}
