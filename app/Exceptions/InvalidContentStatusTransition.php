<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Enums\ContentStatus;
use RuntimeException;

final class InvalidContentStatusTransition extends RuntimeException
{
    public static function make(ContentStatus $from, ContentStatus $to): self
    {
        return new self(sprintf(
            'Invalid content status transition from [%s] to [%s].',
            $from->value,
            $to->value,
        ));
    }
}
