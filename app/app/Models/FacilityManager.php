<?php

declare(strict_types=1);

namespace App\Models;

use Exception;
use Throwable;
use Nette;
use Nette\Database\Explorer;
use Nette\Utils\Random;

class FacilityManager
{
    public function __construct(
        private Explorer $database
    ) {}

    public function createInitialRegistration(string $ico, string $contactPersonName, string $contactPersonSurname, string $email, string $password_hash): string
    {
        $token = $this->generateToken();
        
        $this->database->table('clinics')->insert([
            'ico' => $ico,
            'contact_person_name' => $contactPersonName,
            'contact_person_surname' => $contactPersonSurname,
            'email' => $email,
            'password' => $password_hash,
            'token' => $token,
            'is_email_verified' => 0
        ]);

        return $token;
    }

    public function generateToken(): string
    {
        do {
            $token = Random::generate(32);
        } while ($this->database->table('clinics')->where('token', $token)->fetch());

        return $token;
    }

    public function findByToken(string $token): ?Nette\Database\Table\ActiveRow
    {
        return $this->database->table('clinics')->where('token', $token)->fetch();
    }

    public function completeRegistration(int $id, array $data): void
    {
        $data['is_email_verified'] = 1;

        $this->database->table('clinics')->where('id', $id)->update($data);
    }

    private function applySearch(Nette\Database\Table\Selection $query, array $filters): void
    {
        if (!empty($filters['ico'])) $query->where('ico LIKE ?', "%{$filters['ico']}%");
        if (!empty($filters['name'])) $query->where('name LIKE ?', "%{$filters['name']}%");
        if (!empty($filters['email'])) $query->where('email LIKE ?', "%{$filters['email']}%");
        if (!empty($filters['program_type'])) $query->where('program_type = ?', $filters['program_type']);
        
        if (isset($filters['status']) && $filters['status'] !== '') {
            $status = (int)$filters['status'];
            if ($status === 0) $query->where('is_email_verified = 0 AND is_approved = 0');
            elseif ($status === 4) $query->where('is_email_verified = 1 AND is_approved = 0'); // Čeká
            elseif ($status === 1) $query->where('is_approved = 1');
            elseif ($status === 2) $query->where('is_approved = 2'); // Žádá o změnu
            elseif ($status === 3) $query->where('is_approved = 3'); // Zamítnuto
        }
    }

    public function getClinicsCount(array $filters = []): int
    {
        $query = $this->database->table('clinics');
        $this->applySearch($query, $filters);
        return $query->count('*');
    }

    public function getClinicsPage(int $offset, int $limit, string $sort = 'created_at', string $order = 'DESC', array $filters = [])
    {
        $query = $this->database->table('clinics');
        $this->applySearch($query, $filters);

        if ($sort === 'is_approved') {
            $query->order('is_approved ' . $order . ', is_email_verified ' . $order);
        } else {
            $query->order($sort . ' ' . $order);
        }
        return $query->limit($limit, $offset);
    }

    public function approveClinic(int $id): void
    {
        $this->database->table('clinics')->where('id', $id)->update([
            'is_approved' => 1,
            'deny_reason' => null
        ]);
    }

    public function denyClinic(int $id, string $reason): void
    {
        $this->database->table('clinics')->where('id', $id)->update([
            'is_approved' => 3, 
            'deny_reason' => $reason
        ]);
    }

    public function approveClinicChange(int $id): void
    {
        $this->database->beginTransaction();
        try {
            $data = $this->database->table('clinic_change_requests')->where('clinics_id', $id)->fetch();
            
            if (!$data) {
                $this->database->rollBack();
                return;
            }

            $nonNullData = array_filter($data->toArray(), fn($value) => $value !== null && $value !== '');

            unset($nonNullData['id']);
            unset($nonNullData['clinics_id']);

            $updateData = array_merge($nonNullData, [
                'is_approved' => 1,
                'deny_reason' => null
            ]);
            $this->database->table('clinics')->where('id', $id)->update($updateData);
            $this->database->table('clinic_change_requests')->where('clinics_id', $id)->delete();
            
            $this->database->commit();

        } catch (\Throwable $e) {
            $this->database->rollBack();
            throw $e;
        }
    }

    public function denyClinicChange(int $id): void
    {
        $this->database->beginTransaction();
        try {
            $prev = $this->database->table('clinics')->where('id', $id)->fetchPairs('id', 'prev_is_approved');
            $prev = reset($prev);

            $this->updateClinic($id, ['is_approved' => $prev]);

            $this->database->table('clinic_change_requests')->where('clinics_id', $id)->delete();
            
            $this->database->commit();

        } catch (\Throwable $e) {
            $this->database->rollBack();
            throw $e;
        }
    }

    public function verifyEmail(int $id): void
    {
        $this->database->beginTransaction();
        try{
            $newEmail = $this->database->table('clinics')->where('id', $id)->fetchPairs('id', 'unverified_email');
            $newEmail = reset($newEmail);
            if($newEmail === null) return;

            $this->database->table('clinics')->where('id', $id)->update([
                'email' => $newEmail,
                'unverified_email' => null,
                'is_email_verified' => 1
            ]);

            $this->database->commit();
        } catch (\Throwable $e) {
            $this->database->rollBack();
            throw $e;
        }
    }

    public function clinicChangeRequest(array $data): void
    {
        $this->database->beginTransaction();
        try{
            $changeRequest = $this->getClinicChangeRequest($data['clinics_id']);
            if(!$changeRequest){
                $this->database->table('clinic_change_requests')->insert($data);
            }else{
                $this->database->table('clinic_change_requests')->where('clinics_id', $data['clinics_id'])->update($data);
            }

            $this->database->commit();
        } catch (\Throwable $e) {
            $this->database->rollBack();
            throw $e;
        }
    }

    public function changePassword(int $clinicId, string $password): void
    {
        $token = $this->generateToken();
        $hash = password_hash($password, null);
        $this->updateClinic($clinicId, ['password' => $hash, 'token' => $token]);
    }

    public function getClinic(int $id): ?Nette\Database\Table\ActiveRow
    {
        return $this->database->table('clinics')->get($id);
    }

    public function findByIco(string $ico): ?Nette\Database\Table\ActiveRow
    {
        $ico = preg_replace('#\s+#', '', $ico);
        return $this->database->table('clinics')->where('ico', $ico)->fetch();
    }

    public function getPrograms(): array
    {
        return $this->database->table('programs')->fetchAll();
    }

    public function getProgramNames(): array
    {
        return $this->database->table('programs')->fetchPairs('id', 'name');
    }

    public function getProgramDescriptions(): array
    {
        return $this->database->table('programs')->fetchPairs('id', 'description');
    }

    public function getClinicChangeRequest(int $clinicId): ?Nette\Database\Table\ActiveRow
    {
        return $this->database->table('clinic_change_requests')->where('clinics_id', $clinicId)->fetch();
    }

    public function updateClinic(int $clinicId, array $data): void
    {
        $this->database->table('clinics')->where('id', $clinicId)->update($data);
    }
}