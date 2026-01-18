<?php

declare(strict_types=1);

namespace App\Domain\Model;

enum CardOrigin: string
{
    case AI = 'ai';
    case MANUAL = 'manual';
}
