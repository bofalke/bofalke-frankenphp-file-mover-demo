<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\FileMoverResult;
use App\Model\FileMoverStatus;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

final class FileMover
{
    public function __construct(
        private string $sourceDirectory,
        private string $destinationDirectory,
        private int $fileAgeThreshold,
        private readonly LoggerInterface $logger
    ) {
    }

    public function moveFiles(): FileMoverResult
    {
        $filesystem = new Filesystem();
        $finder = new Finder();
        $filesMoved = 0;

        try {
            $finder->in($this->sourceDirectory)->files()->notName('*.lock')->date('<= now - '.$this->fileAgeThreshold.' minutes');

            foreach ($finder as $file) {
                $destinationPath = $this->destinationDirectory.DIRECTORY_SEPARATOR.$file->getRelativePathname();
                try {
                    $filesystem->copy($file->getRealPath(), $destinationPath, true);
                    $filesystem->remove($file->getRealPath());
                    ++$filesMoved;
                } catch (IOException $e) {
                    $this->logger->error(sprintf('Failed to move file "%s": %s', $file->getRealPath(), $e->getMessage()));
                }
            }
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());

            return new FileMoverResult(FileMoverStatus::FAILURE, $filesMoved, $e);
        }

        return new FileMoverResult(FileMoverStatus::SUCCESS, $filesMoved, null);
    }

    public function setSourceDirectory(string $sourceDirectory): void
    {
        $this->sourceDirectory = $sourceDirectory;
    }

    public function setDestinationDirectory(string $destinationDirectory): void
    {
        $this->destinationDirectory = $destinationDirectory;
    }

    public function setFileAgeThreshold(int $fileAgeThreshold): void
    {
        $this->fileAgeThreshold = $fileAgeThreshold;
    }
}
