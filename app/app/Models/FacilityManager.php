<?php

declare(strict_types=1);

namespace App\Models;

use Nette;
use Nette\Database\Explorer;
use Nette\Utils\Random;

class FacilityManager
{
    public function __construct(
        private Explorer $database
    ) {}

    public function createInitialRegistration(string $ico, string $contactPersonName, string $contactPersonSurname, string $email): string
    {
        do {
            $token = Random::generate(32);
        } while ($this->database->table('clinics')->where('token', $token)->fetch());
        
        $this->database->table('clinics')->insert([
            'ico' => $ico,
            'contact_person_name' => $contactPersonName,
            'contact_person_surname' => $contactPersonSurname,
            'email' => $email,
            'token' => $token,
            'is_email_verified' => 0
        ]);

        return $token;
    }

    public function findByToken(string $token): ?Nette\Database\Table\ActiveRow
    {
        return $this->database->table('clinics')->where('token', $token)->fetch();
    }

    public function completeRegistration(int $id, array $data): void
    {
        $data['is_email_verified'] = 1;
        $data['token'] = null; // Zneplatníme odkaz pro další použití

        $this->database->table('clinics')->where('id', $id)->update($data);
    }

    private function applySearch(Nette\Database\Table\Selection $query, ?string $searchField, ?string $searchQuery): void
    {
        $allowedFields = [
            'contact_person_name', 'contact_person_surname', 'email', 
            'name', 'address_street_number', 'address_city', 'address_ZIP'
        ];

        if ($searchField && $searchQuery && in_array($searchField, $allowedFields, true)) {
            $query->where("$searchField LIKE ?", "%$searchQuery%");
        }
    }

    public function getClinicsCount(?string $searchField = null, ?string $searchQuery = null): int
    {
        $query = $this->database->table('clinics');
        $this->applySearch($query, $searchField, $searchQuery);
        
        return $query->count('*');
    }

    public function getClinicsPage(int $offset, int $limit, string $sort = 'created_at', string $order = 'DESC', ?string $searchField = null, ?string $searchQuery = null)
    {
        $query = $this->database->table('clinics');
        $this->applySearch($query, $searchField, $searchQuery);

        switch($sort){
            case 'is_approved':
                $query->order('((is_approved * 2) + is_email_verified) ' . $order);
                break;
            case 'contact_person':
                $query->order('contact_person_surname ' . $order . ', contact_person_name ' . $order);
                break;
            case 'address':
                $query->order('address_city ' . $order . ', address_ZIP ' . $order . ', address_street_number ' . $order);
                break;
            default:
                $query->order($sort . ' ' . $order);
        }
        return $query->limit($limit, $offset);
    }

    public function approveClinic(int $id): void
    {
        $this->database->table('clinics')
            ->where('id', $id)
            ->update(['is_approved' => 1]);
    }

    public function getClinic(int $id): ?Nette\Database\Table\ActiveRow
    {
        return $this->database->table('clinics')->get($id);
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
}