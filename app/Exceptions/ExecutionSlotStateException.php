<?php

namespace App\Exceptions;

use Exception;

class ExecutionSlotStateException extends Exception
{
    public function statusCode(): int
    {
        return 409;
    }
}
