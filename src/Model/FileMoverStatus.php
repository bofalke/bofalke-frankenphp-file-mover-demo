<?php

declare(strict_types=1);

namespace App\Model;

enum FileMoverStatus: string
{
    case SUCCESS = 'SUCCESS';
    case FAILURE = 'FAILURE';
}
