<?php

declare(strict_types=1);

namespace App\Services;

use Nette\Mail\Mailer;
use Nette\Mail\Message;
use Nette\Utils\FileSystem;

class FileMailer implements Mailer
{
    private string $outputDir;

    public function __construct(string $outputDir)
    {
        $this->outputDir = $outputDir;
    }

    public function send(Message $mail): void
    {
        // Ujistíme se, že cílová složka existuje
        FileSystem::createDir($this->outputDir);

        // Vytvoříme unikátní název souboru podle času a náhodného čísla
        $fileName = 'mail-' . date('Y-m-d-H-i-s') . '-' . mt_rand(1000, 9999) . '.eml';

        // Vygenerujeme surový text e-mailu (včetně HTML těla a hlaviček)
        $rawEmailContents = $mail->generateMessage();
        
        // Uložíme jako soubor
        file_put_contents($this->outputDir . '/' . $fileName, $rawEmailContents);
    }
}