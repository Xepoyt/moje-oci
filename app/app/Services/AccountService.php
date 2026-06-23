<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\FacilityManager;
use Nette\Application\LinkGenerator;
use Nette\Utils\Random;

class AccountService
{
    public function __construct(
        private FacilityManager $facilityManager,
        private EmailService $emailService,
        private LinkGenerator $linkGenerator
    ) {}

    public function changeContact(\stdClass $values, int $clinicId): void
    {
        $clinic = $this->facilityManager->getClinic($clinicId);
        $data = [];
        if($values->contact_person_name !== null && $values->contact_person_name !== $clinic->contact_person_name){
            $data['contact_person_name'] = $values->contact_person_name;
        }
        if($values->contact_person_surname !== null && $values->contact_person_surname !== $clinic->contact_person_surname){
            $data['contact_person_surname'] = $values->contact_person_surname;
        }
        
        if($values->email !== null && $values->email !== $clinic->email){
            $data['email'] = $values->email;
            
            $token = $this->facilityManager->generateToken();
            $data['token'] = $token;

            if($clinic->is_email_verified == 0){
                $link = $this->linkGenerator->link('Registration:Registration:complete', ['token' => $token]);
            }
            else{
                $link = $this->linkGenerator->link('Home:Home:verified', ['token' => $token]);
                $data['is_email_verified'] = 2; 
            }

            $this->emailService->emailChangeOldAddress($clinic->email, $clinic->ico);
            $this->emailService->emailChangeNewAddress($values->email, $link, $clinic->ico);
        }

        $this->facilityManager->updateClinic($clinicId, $data);
    }

    public function resendEmail(int $clinicId): void
    {
        $clinic = $this->facilityManager->getClinic($clinicId);
        if($clinic->is_email_verified == 1) return;

        $token = $this->facilityManager->generateToken();
        $data['token'] = $token;
        $this->facilityManager->updateClinic($clinicId, $data);

        if($clinic->is_email_verified == 0){
            $link = $this->linkGenerator->link('Registration:Registration:complete', ['token' => $token]);
            $this->emailService->sendVerificationEmail($clinic->email, $clinic->contact_person_name . ' ' . $clinic->contact_person_surname, $link);
        }
        elseif($clinic->is_email_verified == 2){
            $link = $this->linkGenerator->link('Home:Home:verified', ['token' => $token]);
            $this->emailService->emailChangeNewAddress($clinic->email, $link, $clinic->ico);
        }
    }
}