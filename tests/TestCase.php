<?php

namespace Test\Lucinda\DB;

use Lucinda\UnitTest\Result;
use Lucinda\UnitTest\Validator\Arrays;
use Lucinda\UnitTest\Validator\Booleans;
use Lucinda\UnitTest\Validator\Files;
use Lucinda\UnitTest\Validator\Integers;
use Lucinda\UnitTest\Validator\Objects;
use Lucinda\UnitTest\Validator\Strings;

abstract class TestCase
{
    protected function assertTrue(bool $condition, string $message = ""): Result
    {
        return (new Booleans($condition))->assertTrue($message);
    }

    protected function assertFalse(bool $condition, string $message = ""): Result
    {
        return (new Booleans($condition))->assertFalse($message);
    }

    protected function assertEquals(mixed $expected, mixed $actual, string $message = ""): Result
    {
        if (is_array($actual) && is_array($expected)) {
            return (new Arrays($actual))->assertEquals($expected, $message);
        }
        if (is_int($actual) && is_int($expected)) {
            return (new Integers($actual))->assertEquals($expected, $message);
        }
        if (is_string($actual) && is_string($expected)) {
            return (new Strings($actual))->assertEquals($expected, $message);
        }

        return (new Strings((string) $actual))->assertEquals((string) $expected, $message);
    }

    protected function assertInstanceOf(string $expected, mixed $actual, string $message = ""): Result
    {
        return is_object($actual)
            ? (new Objects($actual))->assertInstanceOf($expected, $message)
            : (new Booleans(false))->assertTrue($message);
    }

    protected function assertFileExists(string $path, string $message = ""): Result
    {
        return (new Files($path))->assertExists($message);
    }

    protected function assertFileNotExists(string $path, string $message = ""): Result
    {
        return (new Files($path))->assertNotExists($message);
    }

    protected function assertEmptyArray(array $value, string $message = ""): Result
    {
        return (new Arrays($value))->assertEmpty($message);
    }
}
