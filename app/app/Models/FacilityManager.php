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

    public function createInitialRegistration(string $ico, string $contactPerson, string $email): string
    {
        $token = Random::generate(32);
        
        $this->database->table('clinics')->insert([
            'ico' => $ico,
            'contact_person' => $contactPerson,
            'email' => $email,
            'is_authorized' => 1,
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

    public function getClinicsCount(): int
    {
        return $this->database->table('clinics')
            ->count('*');
    }

    public function getClinicsPage(int $offset, int $limit, string $sort = 'created_at', string $order = 'DESC')
    {
        $query = $this->database->table('clinics');
        if ($sort === 'is_approved') {
            $query->order('((is_approved * 2) + is_email_verified) ' . $order);
        } else {
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
}