<?php

namespace Test\Lucinda\DB;

use Lucinda\DB\KeyNotFoundException;
use Lucinda\DB\ValueDriver;
use Lucinda\UnitTest\Result;

/**
 * Verifies replicated values using independent temporary directories per test.
 */
final class ValueDriverTest extends TestCase
{
    /** @return Result[] Every replica receives the same JSON payload */
    public function set(): array
    {
        return $this->runWithReplicas(function ($driver, $schemas) {
            $driver->set(['answer' => 42]);
            $results = [];
            foreach ($schemas as $schema) {
                $results[] = $this->assertEquals(['answer' => 42], json_decode(file_get_contents($schema.'/a_b.json'), true));
            }
            return $results;
        });
    }

    /** @return Result[] A stored payload is readable */
    public function get(): array
    {
        return $this->runWithReplicas(function ($driver) {
            $driver->set(1);
            return [$this->assertEquals(1, $driver->get())];
        });
    }

    /** @return Result[] Presence reflects any available replica */
    public function exists(): array
    {
        return $this->runWithReplicas(function ($driver, $schemas) {
            $results = [$this->assertFalse($driver->exists())];
            $driver->set(1);
            unlink($schemas[0].'/a_b.json');
            $results[] = $this->assertTrue($driver->exists());
            return $results;
        });
    }

    /** @return Result[] Counter increments are copied to both replicas */
    public function increment(): array
    {
        return $this->runWithReplicas(function ($driver, $schemas) {
            $driver->set(1);
            $results = [$this->assertEquals(3, $driver->increment(2))];
            foreach ($schemas as $schema) {
                $results[] = $this->assertEquals(3, json_decode(file_get_contents($schema.'/a_b.json'), true));
            }
            return $results;
        });
    }

    /** @return Result[] Counter decrements are copied to both replicas */
    public function decrement(): array
    {
        return $this->runWithReplicas(function ($driver, $schemas) {
            $driver->set(5);
            $results = [$this->assertEquals(3, $driver->decrement(2))];
            foreach ($schemas as $schema) {
                $results[] = $this->assertEquals(3, json_decode(file_get_contents($schema.'/a_b.json'), true));
            }
            return $results;
        });
    }

    /** @return Result[] Deletion tolerates an absent replica and clears the others */
    public function delete(): array
    {
        return $this->runWithReplicas(function ($driver, $schemas) {
            $driver->set(1);
            unlink($schemas[0].'/a_b.json');
            $driver->delete();
            return [
                $this->assertFalse($driver->exists()),
                $this->assertFileNotExists($schemas[0].'/a_b.json'),
                $this->assertFileNotExists($schemas[1].'/a_b.json')
            ];
        });
    }

    /** @return Result[] Reads repair both malformed and absent replica files */
    public function getRepairsReplicas(): array
    {
        return $this->runWithReplicas(function ($driver, $schemas) {
            $results = [];
            foreach (['corrupted', 'missing'] as $condition) {
                $driver->set(1);
                if ($condition === 'corrupted') {
                    file_put_contents($schemas[0].'/a_b.json', '{');
                } else {
                    unlink($schemas[0].'/a_b.json');
                }
                $results[] = $this->assertEquals(1, $driver->get(), $condition);
                $results[] = $this->assertEquals(1, json_decode(file_get_contents($schemas[0].'/a_b.json'), true), 'repaired '.$condition);
            }
            return $results;
        });
    }

    /** @return Result[] Operations requiring an entry reject an entirely absent key */
    public function rejectsMissingKey(): array
    {
        return $this->runWithReplicas(function ($driver) {
            $results = [];
            foreach (['get', 'increment', 'decrement', 'delete'] as $method) {
                $error = null;
                try {
                    $driver->{$method}();
                } catch (KeyNotFoundException $exception) {
                    $error = $exception;
                }
                $results[] = $this->assertInstanceOf(KeyNotFoundException::class, $error, $method);
            }
            return $results;
        });
    }

    /** @return Result[] Malformed JSON on every replica raises a decoding failure */
    public function rejectsCorruptedReplicas(): array
    {
        return $this->runWithReplicas(function ($driver, $schemas) {
            foreach ($schemas as $schema) {
                file_put_contents($schema.'/a_b.json', '{');
            }
            $error = null;
            try {
                $driver->get();
            } catch (\JsonException $exception) {
                $error = $exception;
            }
            return [$this->assertInstanceOf(\JsonException::class, $error)];
        });
    }

    /**
     * Creates and cleans up two replica folders around one callback.
     *
     * @param callable(ValueDriver, string[]): Result[] $test Callback receiving a driver and its schema paths
     * @return Result[] Assertions returned by the callback
     */
    private function runWithReplicas(callable $test): array
    {
        $directory = sys_get_temp_dir().'/lucinda-value-tests-'.bin2hex(random_bytes(8));
        mkdir($directory);
        $schemas = [$directory.'/first', $directory.'/second'];
        try {
            foreach ($schemas as $schema) {
                mkdir($schema);
            }
            return $test(new ValueDriver($schemas, ['b', 'a']), $schemas);
        } finally {
            foreach ($schemas as $schema) {
                if (is_dir($schema)) {
                    foreach (glob($schema.'/*') as $file) {
                        unlink($file);
                    }
                    rmdir($schema);
                }
            }
            rmdir($directory);
        }
    }
}
