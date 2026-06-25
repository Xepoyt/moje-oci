<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\FacilityManager;
use App\Exceptions\ClinicNotFoundException;
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
        
        if ($values->contact_person_name !== null && $values->contact_person_name !== '' && $values->contact_person_name !== $clinic->contact_person_name) {
            $data['contact_person_name'] = $values->contact_person_name;
        }
        
        if ($values->contact_person_surname !== null && $values->contact_person_surname !== '' && $values->contact_person_surname !== $clinic->contact_person_surname) {
            $data['contact_person_surname'] = $values->contact_person_surname;
        }
        
        if ($values->email !== null && $values->email !== '' && $values->email !== $clinic->email) {
            
            $token = $this->facilityManager->generateToken();
            $data['token'] = $token;

            if ($clinic->is_email_verified == 0) {
                $data['email'] = $values->email;
                $link = $this->linkGenerator->link('Registration:Registration:complete', ['token' => $token]);
            } else {
                $data['unverified_email'] = $values->email;
                $link = $this->linkGenerator->link('Home:Home:verified', ['token' => $token]);
                $data['is_email_verified'] = 2; 
            }

            $this->emailService->emailChangeOldAddress($clinic->email, $clinic->ico);
            $this->emailService->emailChangeNewAddress($values->email, $link, $clinic->ico);
        }

        if (!empty($data)) {
            $this->facilityManager->updateClinic($clinicId, $data);
        }
    }

    public function changeClinic(\stdClass $values, int $clinicId): void
    {
        $clinic = $this->facilityManager->getClinic($clinicId);
        $data = [];

        // 1. Předzpracování dat (odstranění mezer), pokud data existují
        if ($values->program_type === 1 && isset($values->reservation_phone)) {
            $values->reservation_phone = preg_replace('#\s+#', '', $values->reservation_phone);
        }
        if (isset($values->address_ZIP)) {
            $values->address_ZIP = preg_replace('#\s+#', '', $values->address_ZIP);
        }
        
        // 2. Porovnání povinných textových/číselných položek (s kontrolou na prázdný string)
        if ($values->name !== null && $values->name !== '' && $values->name !== $clinic->name) {
            $data['name'] = $values->name;
        }
        if ($values->address_street_number !== null && $values->address_street_number !== '' && $values->address_street_number !== $clinic->address_street_number) {
            $data['address_street_number'] = $values->address_street_number;
        }
        if ($values->address_city !== null && $values->address_city !== '' && $values->address_city !== $clinic->address_city) {
            $data['address_city'] = $values->address_city;
        }
        if ($values->address_ZIP !== null && $values->address_ZIP !== '' && $values->address_ZIP !== $clinic->address_ZIP) {
            $data['address_ZIP'] = $values->address_ZIP;
        }
        if ($values->web !== null && $values->web !== '' && $values->web !== $clinic->web) {
            $data['web'] = $values->web;
        }
        if ($values->description !== null && $values->description !== '' && $values->description !== $clinic->description) {
            $data['description'] = $values->description;
        }
        if ($values->program_type !== null && $values->program_type !== '' && $values->program_type !== $clinic->program_type) {
            $data['program_type'] = $values->program_type;
        }

        // 3. Porovnání volitelných položek
        $resEmail = $values->reservation_email ?? null;
        if ($resEmail !== null && $resEmail !== '' && $resEmail !== $clinic->reservation_email) {
            $data['reservation_email'] = $resEmail;
        }

        $resPhone = $values->reservation_phone ?? null;
        if ($resPhone !== null && $resPhone !== '' && $resPhone !== $clinic->reservation_phone) {
            $data['reservation_phone'] = $resPhone;
        }

        $maxPatients = $values->max_patients ?? null;
        if ($maxPatients !== null && $maxPatients !== $clinic->max_patients) {
            $data['max_patients'] = $maxPatients;
        }

        // 4. Update pouze pokud došlo k nějaké změně
        if (!empty($data)) {
            // Přepnutí stavu kliniky na "čeká na schválení změn"
            if ($clinic->is_approved == 1 || $clinic->is_approved == 3) {
                $this->facilityManager->updateClinic($clinicId, ['is_approved' => 2, 'prev_is_approved' => $clinic->is_approved]);
                $this->emailService->sendAdminClinicChangeNotification($clinic->name, $clinic->ico, $clinic->contact_person_name . " " . $clinic->contact_person_surname);

                $data['clinics_id'] = $clinicId;
                $this->facilityManager->clinicChangeRequest($data);
            }
            else{
                $this->facilityManager->updateClinic($clinicId, $data);
            }
        }
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
            $this->emailService->emailChangeNewAddress($clinic->unverified_email, $link, $clinic->ico);
        }
    }

    public function resetPassword(string $ico): void
    {
        $clinic = $this->facilityManager->findByIco($ico);
        if(!$clinic){
            throw new ClinicNotFoundException("V naší databázi neexistuje klinika s IČO " . $ico);
        }

        $link = $this->linkGenerator->link('Login:Login:reset', ['token' => $clinic->token]);
        $this->emailService->sendPasswordResetEmail($clinic->email, $clinic->contact_person_name . " " . $clinic->contact_person_surname, $link);
    }
}