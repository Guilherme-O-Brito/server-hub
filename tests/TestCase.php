<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Str;

abstract class TestCase extends BaseTestCase
{
    protected function operationId(int $fixture): string
    {
        return sprintf('00000000-0000-4000-8000-%012d', $fixture);
    }

    protected function assertValidOperationId(?string $operationId): void
    {
        $this->assertNotNull($operationId);
        $this->assertTrue(Str::isUuid($operationId));
    }
}
