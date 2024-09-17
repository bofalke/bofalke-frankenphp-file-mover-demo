<?php

declare(strict_types=1);

namespace App\Tests;

use DirectoryIterator;
use SplFileInfo;

trait FixtureCleanupTrait
{
    private function recursiveDelete(SplFileInfo $path): void
    {
        if ($path->isDir()) {
            foreach (new DirectoryIterator($path->getRealPath()) as $item) {
                if ($item->isDot()) {
                    continue;
                }

                $this->recursiveDelete($item);
            }

            return;
        }

        if ($path->isFile() && !str_starts_with($path->getFilename(), '.')) {
            unlink($path->getRealPath());

            return;
        }

        if ($path->isFile() && str_ends_with($path->getFilename(), '.lock')) {
            unlink($path->getRealPath());
        }
    }

    protected function setUp(): void
    {
        /** @var SplFileInfo $item */
        foreach (new DirectoryIterator(__DIR__.'/fixtures') as $item) {
            if ($item->isDot()) {
                continue;
            }

            $this->recursiveDelete($item);
        }
        parent::setUp();
    }

    protected function tearDown(): void
    {
        /** @var SplFileInfo $item */
        foreach (new DirectoryIterator(__DIR__.'/fixtures') as $item) {
            if ($item->isDot()) {
                continue;
            }

            $this->recursiveDelete($item);
        }
        parent::setUp();
    }
}
