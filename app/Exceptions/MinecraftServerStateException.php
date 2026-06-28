<?php

namespace App\Exceptions;

use Exception;

class MinecraftServerStateException extends Exception
{
    public function statusCode(): int
    {
        return 409;
    }
}
