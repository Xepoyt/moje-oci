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
        private LatteFactory $latteFactory
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
        $mail->setFrom('registrace@mojeoci.cz', 'MOJE OČI - Portál')
            ->addTo($email)
            ->setSubject('Dokončení registrace zařízení')
            ->setHtmlBody($htmlBody);

        $this->mailer->send($mail);
    }
    
    //TODO: sendApprovalNotification
}