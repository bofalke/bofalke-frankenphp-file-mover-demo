<?php

declare(strict_types=1);

namespace App\Command;

use App\Model\StaleFileDetectorStatus;
use App\Service\StaleFileDetector;
use App\Service\StaleFileMailer;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function count;

#[AsCommand(
    name: 'app:notify-stale-files',
    description: 'Detects stale files from a directory and sends a notification.',
    hidden: false,
)]
final class NotifyForStaleFilesCommand extends Command
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly StaleFileMailer $staleFileMailer
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('source', InputArgument::OPTIONAL, 'The source directory path.', null)
            ->addArgument(
                'threshold',
                InputArgument::OPTIONAL,
                'The time threshold in minutes. Defaults to 24h',
                '1440'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $source = $input->getArgument('source');
        $time = $input->getArgument('threshold');

        if (null === $source) {
            $output->writeln('Error: "No source path specified. Try app:notify-stale-files /path/to/source"');

            return Command::FAILURE;
        }

        $staleFilesResult = StaleFileDetector::detectStaleFiles($source, $time);

        if (StaleFileDetectorStatus::FAILURE === $staleFilesResult->status) {
            $outputMessage = sprintf(
                'Error: "%s"',
                $staleFilesResult->exception?->getMessage() ?? 'No exception message available.'
            );
            $this->logger->info($outputMessage);
            $output->writeln($outputMessage);

            return Command::FAILURE;
        }

        if (StaleFileDetectorStatus::LOCK_FILE_FOUND === $staleFilesResult->status && 0 === count($staleFilesResult->filesMoved)) {
            $output->writeln('Lock file(s) found. Skipped Stale file monitoring.');

            return Command::SUCCESS;
        }

        if (count($staleFilesResult->filesMoved) > 0) {
            $outputMessage = $this->staleFileMailer->buildMailBody($staleFilesResult);
            $this->staleFileMailer->sendMail($outputMessage);
            $output->writeln($outputMessage);

            return Command::SUCCESS;
        }

        $output->writeln('No stale files found!');

        return Command::SUCCESS;
    }
}
