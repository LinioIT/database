<?php

declare(strict_types=1);

namespace Linio\Component\Database\Exception;

use RuntimeException;
use Throwable;

class DatabaseException extends RuntimeException
{
    public function __construct($message = '', $code = 0, Throwable $previous = null)
    {
        parent::__construct((string) $message, (int) $code, $previous);
    }
}
