<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\FacilityManager;
use Nette\Application\LinkGenerator;

class RegistrationService
{
    public function __construct(
        private FacilityManager $facilityManager,
        private EmailService $emailService,
        private LinkGenerator $linkGenerator
    ) {}

    public function initiateRegistration(\stdClass $values): void
    {
        $token = $this->facilityManager->createInitialRegistration(
            $values->ico, 
            $values->contact_person_name,
            $values->contact_person_surname, 
            $values->email
        );
        
        // LinkGenerator vyžaduje plný název cíle
        $link = $this->linkGenerator->link('Registration:Registration:complete', ['token' => $token]);
        
        $this->emailService->sendVerificationEmail($values->email, $values->contact_person_name . ' ' . $values->contact_person_surname, $link);
    }

    public function completeRegistration(int $clinicId, array $values): void
    {
        // Ošetření schovaných polí podle programu
        if ($values['program_type'] === 3) {
            $values['reservation_email'] = null;
            $values['reservation_phone'] = null;
            $values['max_patients'] = null;
        } elseif ($values['program_type'] === 2) {
            $values['reservation_phone'] = null;
        }

        $this->facilityManager->completeRegistration($clinicId, $values);
        
        $clinic = $this->facilityManager->getClinic($clinicId);
        if ($clinic) {
            $this->emailService->sendWaitingForApprovalEmail(
                $clinic->email, 
                $clinic->contact_person_name . ' ' . $clinic->contact_person_surname, 
                $clinic->name
            );
        }
    }

    public function approveClinic(int $clinicId): void
    {
        $this->facilityManager->approveClinic($clinicId);

        $clinic = $this->facilityManager->getClinic($clinicId);

        if ($clinic) {
            $this->emailService->sendApprovalNotification(
                $clinic->email,
                $clinic->name
            );
        }
    }
}