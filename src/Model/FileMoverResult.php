<?php

declare(strict_types=1);

namespace App\Model;

use Exception;

final readonly class FileMoverResult
{
    public function __construct(
        public FileMoverStatus $status,
        public int             $filesMoved,
        public ?Exception      $exception = null,
    ) {
    }
}
