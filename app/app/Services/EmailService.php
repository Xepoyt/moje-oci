<?php

declare(strict_types=1);

namespace App\Services;

use Nette\Mail\Mailer;
use Nette\Mail\Message;
use Nette\Bridges\ApplicationLatte\LatteFactory; 

class EmailService
{
    public function __construct(
        private Mailer $mailer,
        private LatteFactory $latteFactory,
        private string $adminEmail
    ) {}

    public function sendVerificationEmail(string $email, string $contactPerson, string $verificationLink): void
    {
        $params = [
            'contactPerson' => $contactPerson,
            'link' => $verificationLink
        ];

        $latte = $this->latteFactory->create();
        
        $htmlBody = $latte->renderToString(__DIR__ . '/../Mails/verification.latte', $params);

        $mail = new Message;
        $mail->setFrom('moje-oci@seznam.cz', 'MOJE OČI - Portál')
            ->addTo($email)
            ->setSubject('Dokončení registrace zařízení')
            ->setHtmlBody($htmlBody);

        $this->mailer->send($mail);
    }
    
    public function sendWaitingForApprovalEmail(string $email,string $contactPerson, string $facilityName): void
    {
        $params = [
            'contactPerson' => $contactPerson,
            'facilityName' => $facilityName
        ];

        $latte = $this->latteFactory->create();
        
        $htmlBody = $latte->renderToString(__DIR__ . '/../Mails/registrationWaiting.latte', $params);

        $mail = new Message;
        $mail->setFrom('moje-oci@seznam.cz', 'MOJE OČI - Portál')
            ->addTo($email)
            ->setSubject('Registrace dokončena - čeká na schválení')
            ->setHtmlBody($htmlBody);

        $this->mailer->send($mail);
    }

    public function sendApprovalNotification(string $email, string $facilityName): void
    {
        $params = [
            'facilityName' => $facilityName,
        ];

        $latte = $this->latteFactory->create();
        
        $htmlBody = $latte->renderToString(__DIR__ . '/../Mails/registrationApproved.latte', $params);

        $mail = new Message;
        $mail->setFrom('moje-oci@seznam.cz', 'MOJE OČI - Portál')
            ->addTo($email)
            ->setSubject('Vaše registrace byla schválena!')
            ->setHtmlBody($htmlBody);

        $this->mailer->send($mail);
    }

    public function sendAdminNewRegistrationNotification(string $facilityName, string $ico, string $contactPerson): void
    {
        $params = [
            'facilityName' => $facilityName,
            'ico' => $ico,
            'contactPerson' => $contactPerson
        ];

        $latte = $this->latteFactory->create();
        
        $htmlBody = $latte->renderToString(__DIR__ . '/../Mails/newRegistrationAdmin.latte', $params);

        $mail = new Message;
        $mail->setFrom('moje-oci@seznam.cz', 'MOJE OČI - Portál')
            ->addTo($this->adminEmail)
            ->setSubject('Nová registrace ke schválení: ' . $facilityName)
            ->setHtmlBody($htmlBody);

        $this->mailer->send($mail);
    }

    public function sendClinicDeniedEmail(string $email, string $facilityName, string $reason, int $previousState): void
    {

        $params = [
            'facilityName' => $facilityName,
            'reason' => $reason,
            'previousState' => $previousState
        ];

        $latte = $this->latteFactory->create();
        
        $htmlBody = $latte->renderToString(__DIR__ . '/../Mails/registrationDenied.latte', $params);

        $mail = new Message;
        $mail->setFrom('moje-oci@seznam.cz', 'MOJE OČI - Portál')
            ->addTo($email)
            ->setHtmlBody($htmlBody);
        switch ($previousState) {
            case 0:
                $mail->setSubject('Vaše registrace nebyla schválena');
                break;
            case 1:
                $mail->setSubject('Vaše registrace byla zrušena administrátorem');
                break;
            case 2:
                $mail->setSubject('Vaše žádost o změnu registrace byla zamítnuta');
                break;
            default:
                $mail->setSubject('Vaše registrace byla zamítnuta administrátorem');
                break;
        }
        $this->mailer->send($mail);
    }
}