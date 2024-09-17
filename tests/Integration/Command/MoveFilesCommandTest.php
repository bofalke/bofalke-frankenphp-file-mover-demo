<?php

declare(strict_types=1);

namespace App\Tests\Integration\Command;

use App\Tests\FixtureCleanupTrait;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Tester\CommandTester;

final class MoveFilesCommandTest extends KernelTestCase
{
    use FixtureCleanupTrait;

    /**
     * @test
     */
    public function moving_files(): void
    {
        $kernel = self::bootKernel();
        $app = new Application($kernel);

        $command = $app->find('app:move-files');
        $commandTester = new CommandTester($command);

        touch(__DIR__.'/../../fixtures/source/file_1', time() - 300);
        touch(__DIR__.'/../../fixtures/source/file_2', time() - 300);

        $commandTester->execute(
            [
                'source' => __DIR__.'/../../fixtures/source',
                'destination' => __DIR__.'/../../fixtures/destination',
                'time' => 5,
            ],
            [
                'verbosity' => ConsoleOutput::VERBOSITY_VERBOSE,
            ]
        );

        $output = $commandTester->getDisplay();

        self::assertStringContainsString('Successfully moved 2 file(s).', $output);
    }

    /**
     * @test
     */
    public function moving_only_files_above_threshhold(): void
    {
        $kernel = self::bootKernel();
        $app = new Application($kernel);

        $command = $app->find('app:move-files');
        $commandTester = new CommandTester($command);

        touch(__DIR__.'/../../fixtures/source/file_1', time() - 121);
        touch(__DIR__.'/../../fixtures/source/file_2', time() - 119);

        $commandTester->execute(
            [
                'source' => __DIR__.'/../../fixtures/source',
                'destination' => __DIR__.'/../../fixtures/destination',
                'time' => 2,
            ],
            [
                'verbosity' => ConsoleOutput::VERBOSITY_VERBOSE,
            ]
        );

        $output = $commandTester->getDisplay();

        self::assertStringContainsString('Successfully moved 1 file(s).', $output);
    }

    /**
     * @test
     */
    public function skips_files_newer_than_threshhold(): void
    {
        $kernel = self::bootKernel();
        $app = new Application($kernel);

        $command = $app->find('app:move-files');
        $commandTester = new CommandTester($command);

        touch(__DIR__.'/../../fixtures/source/file_1', time() - 59);
        touch(__DIR__.'/../../fixtures/source/file_2', time() - 59);

        $commandTester->execute(
            [
                'source' => __DIR__.'/../../fixtures/source',
                'destination' => __DIR__.'/../../fixtures/destination',
                'time' => 1,
            ],
            [
                'verbosity' => ConsoleOutput::VERBOSITY_VERBOSE,
            ]
        );

        $output = $commandTester->getDisplay();

        self::assertStringContainsString('No files were moved.', $output);
    }
}
