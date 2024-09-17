<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\StaleFileDetectorResult;
use App\Model\StaleFileDetectorStatus;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Throwable;

use function count;

final readonly class StaleFileMailer
{
    public function __construct(
        private string $staleFileMailFrom,
        private string $staleFileMailTo,
        private string $staleFileMailSubject,
        private MailerInterface $mailer,
        private LoggerInterface $logger
    ) {
    }

    public function buildMailBody(StaleFileDetectorResult $staleFilesResult): string
    {
        if (StaleFileDetectorStatus::LOCK_FILE_FOUND === $staleFilesResult->status && count($staleFilesResult->filesMoved) > 0) {
            return 'Stale Lock file(s) found: ['.implode(', ', $staleFilesResult->filesMoved).']. Skipped Stale file monitoring.';
        }

        $outputMessage = '';
        foreach ($staleFilesResult->filesMoved as $file) {
            $outputMessage .= 'Name: '.$file."\n";
        }

        return $outputMessage;
    }

    public function sendMail(string $mailBody): void
    {
        try {
            $this->mailer->send(
                (new Email())
                    ->from($this->staleFileMailFrom)
                    ->to($this->staleFileMailTo)
                    ->subject($this->staleFileMailSubject)
                    ->text($mailBody)
            );
        } catch (Throwable $exception) {
            $this->logger->error($exception->getMessage());
        }
    }
}
