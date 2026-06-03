<?php

declare(strict_types=1);

namespace App\Modules\Shared\Exceptions;

use RuntimeException;

class BusinessException extends RuntimeException
{
    public function __construct(
        string           $message,
        private readonly int $httpStatus = 422,
        ?\Throwable      $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function getHttpStatus(): int
    {
        return $this->httpStatus;
    }
}
