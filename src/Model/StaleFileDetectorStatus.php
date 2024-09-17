<?php

declare(strict_types=1);

namespace App\Model;

enum StaleFileDetectorStatus: string
{
    case SUCCESS = 'SUCCESS';
    case FAILURE = 'FAILURE';
    case LOCK_FILE_FOUND = 'LOCK_FILE_FOUND';
}
