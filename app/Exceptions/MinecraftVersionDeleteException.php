<?php

namespace App\Exceptions;

use Exception;

class MinecraftVersionDeleteException extends Exception
{
    public function statusCode(): int
    {
        return 409;
    }
}
