<?php

declare(strict_types=1);

namespace App\Command;

use App\Model\FileMoverStatus;
use App\Service\FileMover;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:move-files',
    description: 'Moves files from a source directory to a destination directory if they are older than a specified time.',
    hidden: false,
)]
final class MoveFilesCommand extends Command
{
    public function __construct(private FileMover $fileMover)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Moves files from a specified source directory to a specified destination directory if they are older than the specified time.')
            ->addArgument('source', InputArgument::OPTIONAL, 'The source directory path if not provided env path will be used')
            ->addArgument('destination', InputArgument::OPTIONAL, 'The destination directory path  if not provided env path will be used')
            ->addArgument('time', InputArgument::OPTIONAL, 'The time threshold in minutes if not provided env path will be used');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $source = $input->getArgument('source');
        $destination = $input->getArgument('destination');
        $time = $input->getArgument('time');

        // Updating FileMover's properties if arguments are provided
        if (null !== $source) {
            $this->fileMover->setSourceDirectory($source);
        }
        if (null !== $destination) {
            $this->fileMover->setDestinationDirectory($destination);
        }
        if (null !== $time) {
            $this->fileMover->setFileAgeThreshold((int) $time);
        }

        $result = $this->fileMover->moveFiles();

        if (FileMoverStatus::FAILURE === $result->status) {
            $output->writeln(sprintf('Error: "%s"', $result->exception?->getMessage() ?? 'No exception message available.'));

            return Command::FAILURE;
        }

        if (0 === $result->filesMoved) {
            $output->writeln('No files were moved.');

            return Command::SUCCESS;
        }

        $output->writeln(sprintf('Successfully moved %d file(s).', $result->filesMoved));

        return Command::SUCCESS;
    }
}
