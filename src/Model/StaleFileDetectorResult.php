<?php

declare(strict_types=1);

namespace App\Model;

use Exception;

final readonly class StaleFileDetectorResult
{
    /**
     * @param string[] $filesMoved
     */
    public function __construct(
        public StaleFileDetectorStatus $status,
        public array                   $filesMoved,
        public ?Exception              $exception = null,
    ) {
    }
}
