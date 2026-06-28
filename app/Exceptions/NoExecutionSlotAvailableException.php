<?php

namespace App\Exceptions;

use Exception;

class NoExecutionSlotAvailableException extends Exception
{
    public function statusCode(): int
    {
        return 409;
    }
}
