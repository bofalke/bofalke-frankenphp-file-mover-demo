<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\StaleFileDetectorResult;
use App\Model\StaleFileDetectorStatus;
use Exception;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

final class StaleFileDetector
{
    public static function detectStaleFiles(string $sourceDirectory, string $fileAgeThreshold): StaleFileDetectorResult
    {
        $finder = new Finder();

        try {
            $lockFileCheck = self::checkForLockFile($sourceDirectory, $fileAgeThreshold);
            if (false !== $lockFileCheck) {
                return $lockFileCheck;
            }

            $finder->in($sourceDirectory)->files()->notName('*.lock')->date('<= now - '.$fileAgeThreshold.' minutes');
            $staleFiles = array_map(
                static fn (SplFileInfo $file) => $file->getFileName(), iterator_to_array($finder->getIterator())
            );
        } catch (Exception $e) {
            return new StaleFileDetectorResult(StaleFileDetectorStatus::FAILURE, [], $e);
        }

        return new StaleFileDetectorResult(StaleFileDetectorStatus::SUCCESS, $staleFiles, null);
    }

    private static function checkForLockFile(string $sourceDirectory, $fileAgeThreshold): StaleFileDetectorResult|false
    {
        $finder = new Finder();
        $finder->ignoreDotFiles(false);
        if (0 === iterator_count($finder->in($sourceDirectory)->files()->name(['.lock', '*.lock']))) {
            return false;
        }

        if (iterator_count($finder->in($sourceDirectory)->files()->name(['.lock', '*.lock'])->date('<= now - '.$fileAgeThreshold.' minutes')) > 0) {
            $staleFiles = array_map(
                static fn (SplFileInfo $file) => $file->getFileName(), iterator_to_array($finder->getIterator())
            );

            return new StaleFileDetectorResult(StaleFileDetectorStatus::LOCK_FILE_FOUND, $staleFiles);
        }

        return new StaleFileDetectorResult(StaleFileDetectorStatus::LOCK_FILE_FOUND, []);
    }
}
