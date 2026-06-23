<?php declare(strict_types=1);

namespace App\Security;

use Nette\Security\Authenticator;
use Nette\Security\IIdentity;
use Nette\Security\SimpleIdentity;
use Nette\Security\AuthenticationException;
use Nette\Security\Passwords;
use App\Models\FacilityManager;

class ClinicAuthenticator implements Authenticator
{
    public function __construct(
        private FacilityManager $facilityManager,
        private Passwords $passwords
    ) {}

    public function authenticate(string $ico, string $password): IIdentity
    {
        $ico = preg_replace('#\s+#', '', $ico);
        $row = $this->facilityManager->findByIco($ico);

        if (!$row) {
            throw new AuthenticationException('Zadané IČO není u nás registrováno.');
        }

        if (!$this->passwords->verify($password, $row->password)) {
            throw new AuthenticationException('Zadali jste nesprávné heslo.');
        }

        // Vracíme ID kliniky, roli (např. 'clinic') a užitečná data do session
        return new SimpleIdentity(
            $row->id,
            'clinic',
            ['name' => $row->name, 'ico' => $row->ico, 'email' => $row->email]
        );
    }
}