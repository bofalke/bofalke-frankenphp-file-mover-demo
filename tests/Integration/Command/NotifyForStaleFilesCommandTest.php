<?php

declare(strict_types=1);

namespace App\Tests\Integration\Command;

use App\Tests\FixtureCleanupTrait;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Tester\CommandTester;

final class NotifyForStaleFilesCommandTest extends KernelTestCase
{
    use FixtureCleanupTrait;

    /**
     * @test
     */
    public function detect_stale_files(): void
    {
        self::bootKernel();

        $app = new Application(self::$kernel);

        $command = $app->find('app:notify-stale-files');
        $commandTester = new CommandTester($command);

        touch(__DIR__.'/../../fixtures/source/file_1', time() - 86500);
        touch(__DIR__.'/../../fixtures/source/file_2', time() - 86500);

        $commandTester->execute(
            [
                'source' => __DIR__.'/../../fixtures/source',
            ],
            [
                'verbosity' => ConsoleOutput::VERBOSITY_VERBOSE,
            ]
        );

        self::assertEmailCount(1);
        $email = self::getMailerMessage();
        self::assertEmailTextBodyContains($email, 'Name: file_2', $email->toString());
        self::assertEmailTextBodyContains($email, 'Name: file_1', $email->toString());

        $output = $commandTester->getDisplay();

        self::assertStringContainsString('Name: file_2', $output);
        self::assertStringContainsString('Name: file_1', $output);
    }

    /**
     * @test
     */
    public function skip_on_lock_files(): void
    {
        self::bootKernel();

        $app = new Application(self::$kernel);

        $command = $app->find('app:notify-stale-files');
        $commandTester = new CommandTester($command);

        touch(__DIR__.'/../../fixtures/source/working.lock', time());

        $commandTester->execute(
            [
                'source' => __DIR__.'/../../fixtures/source',
            ],
            [
                'verbosity' => ConsoleOutput::VERBOSITY_VERBOSE,
            ]
        );

        self::assertEmailCount(0);

        $output = $commandTester->getDisplay();

        self::assertStringContainsString('Lock file(s) found. Skipped Stale file monitoring.', $output);
    }

    /**
     * @test
     */
    public function notify_on_stale_lock_file(): void
    {
        self::bootKernel();

        $app = new Application(self::$kernel);

        $command = $app->find('app:notify-stale-files');
        $commandTester = new CommandTester($command);

        touch(__DIR__.'/../../fixtures/source/working.lock', time() - 86500);

        $commandTester->execute(
            [
                'source' => __DIR__.'/../../fixtures/source',
            ],
            [
                'verbosity' => ConsoleOutput::VERBOSITY_VERBOSE,
            ]
        );

        self::assertEmailCount(1);
        $email = self::getMailerMessage();
        self::assertEmailTextBodyContains($email, 'Stale Lock file(s) found: [working.lock]. Skipped Stale file monitoring.');

        $output = $commandTester->getDisplay();

        self::assertStringContainsString('Stale Lock file(s) found: [working.lock]. Skipped Stale file monitoring.', $output);
    }

    /**
     * @test
     */
    public function success_on_no_stale_files(): void
    {
        self::bootKernel();

        $app = new Application(self::$kernel);

        $command = $app->find('app:notify-stale-files');
        $commandTester = new CommandTester($command);

        touch(__DIR__.'/../../fixtures/source/file_1');
        touch(__DIR__.'/../../fixtures/source/file_2');

        $commandTester->execute(
            [
                'source' => __DIR__.'/../../fixtures/source',
            ],
            [
                'verbosity' => ConsoleOutput::VERBOSITY_VERBOSE,
            ]
        );

        self::assertEmailCount(0);
        $output = $commandTester->getDisplay();

        self::assertStringContainsString('No stale files found!', $output);
    }

    /**
     * @test
     */
    public function it_throws_for_missing_argument(): void
    {
        self::bootKernel();

        $app = new Application(self::$kernel);

        $command = $app->find('app:notify-stale-files');
        $commandTester = new CommandTester($command);

        $commandTester->execute(
            [],
            [
                'verbosity' => ConsoleOutput::VERBOSITY_VERBOSE,
            ]
        );

        $output = $commandTester->getDisplay();
        self::assertStringContainsString('Error: "No source path specified. Try app:notify-stale-files /path/to/source"', $output);
    }

    /**
     * @test
     */
    public function errors_caught_properly(): void
    {
        self::bootKernel();

        $app = new Application(self::$kernel);

        $command = $app->find('app:notify-stale-files');
        $commandTester = new CommandTester($command);

        $commandTester->execute(
            [
                'source' => __DIR__.'/../../fixtures/source/does/not/exist',
            ],
            [
                'verbosity' => ConsoleOutput::VERBOSITY_VERBOSE,
            ]
        );

        $output = $commandTester->getDisplay();
        self::assertStringContainsString('Error: "The "'.__DIR__.'/../../fixtures/source/does/not/exist" directory does not exist.', $output);
    }
}
