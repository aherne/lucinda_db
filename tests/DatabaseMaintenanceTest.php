<?php

namespace Test\Lucinda\DB;

use Lucinda\DB\Configuration;
use Lucinda\DB\ConfigurationException;
use Lucinda\DB\DatabaseMaintenance;
use Lucinda\DB\Schema;
use Lucinda\DB\SchemaStatus;
use Lucinda\UnitTest\Result;

/**
 * Exercises maintenance against disposable XML and replicas for each test.
 *
 * Fixtures use absolute paths and are removed in finally blocks. Every public
 * test can run without depending on mutations performed by another test.
 */
final class DatabaseMaintenanceTest extends TestCase
{
    /** @return Result[] Health statuses for writable and missing schemas */
    public function checkHealth(): array
    {
        return $this->runWithSchemas(function ($maintenance, $schemas) {
            $results = [$this->assertEquals(
                [$schemas[0] => SchemaStatus::ONLINE, $schemas[1] => SchemaStatus::ONLINE],
                $maintenance->checkHealth(1)
            )];
            (new Schema($schemas[1]))->drop();
            $results[] = $this->assertEquals(
                [$schemas[0] => SchemaStatus::ONLINE, $schemas[1] => SchemaStatus::OFFLINE],
                $maintenance->checkHealth(1)
            );
            return $results;
        });
    }

    /** @return Result[] Import, duplicate rejection, and cached-schema assertions */
    public function plugIn(): array
    {
        return $this->runWithSchemas(function ($maintenance, $schemas, $xmlPath) {
            $maintenance->plugIn($schemas[2]);
            $error = null;
            try {
                $maintenance->plugIn($schemas[2]);
            } catch (ConfigurationException $exception) {
                $error = $exception;
            }
            return [
                $this->assertEquals(4, (new Schema($schemas[2]))->getCapacity()),
                $this->assertEquals(filemtime($schemas[0].'/a_b.json'), filemtime($schemas[2].'/a_b.json')),
                $this->assertEquals($schemas, (new Configuration(simplexml_load_file($xmlPath)))->getSchemas()),
                $this->assertEquals($schemas, array_keys($maintenance->checkHealth(1))),
                $this->assertInstanceOf(ConfigurationException::class, $error)
            ];
        });
    }

    /** @return Result[] Same-instance plug-in, removal, and re-plug assertions */
    public function plugOut(): array
    {
        return $this->runWithSchemas(function ($maintenance, $schemas, $xmlPath) {
            $maintenance->plugIn($schemas[2]);
            $maintenance->plugOut($schemas[2]);
            $results = [
                $this->assertEquals(0, (new Schema($schemas[2]))->getCapacity()),
                $this->assertTrue(is_dir($schemas[2])),
                $this->assertEquals(array_slice($schemas, 0, 2), (new Configuration(simplexml_load_file($xmlPath)))->getSchemas()),
                $this->assertEquals(array_slice($schemas, 0, 2), array_keys($maintenance->checkHealth(1)))
            ];
            $maintenance->plugIn($schemas[2]);
            $results[] = $this->assertEquals(4, (new Schema($schemas[2]))->getCapacity());
            return $results;
        });
    }

    /** @return Result[] Direct registration updates XML/cache without importing */
    public function addSchema(): array
    {
        return $this->runWithSchemas(function ($maintenance, $schemas, $xmlPath) {
            $maintenance->addSchema($schemas[2]);
            return [
                $this->assertEquals($schemas, (new Configuration(simplexml_load_file($xmlPath)))->getSchemas()),
                $this->assertEquals($schemas, array_keys($maintenance->checkHealth(1))),
                $this->assertEquals(0, (new Schema($schemas[2]))->getCapacity())
            ];
        });
    }

    /** @return Result[] Direct removal preserves data and protects the last schema */
    public function removeSchema(): array
    {
        return $this->runWithSchemas(function ($maintenance, $schemas, $xmlPath) {
            $maintenance->removeSchema($schemas[1]);
            $results = [
                $this->assertEquals([$schemas[0]], (new Configuration(simplexml_load_file($xmlPath)))->getSchemas()),
                $this->assertEquals([$schemas[0]], array_keys($maintenance->checkHealth(1))),
                $this->assertEquals(4, (new Schema($schemas[1]))->getCapacity())
            ];
            $originalXml = file_get_contents($xmlPath);
            foreach (['removeSchema', 'plugOut'] as $method) {
                $error = null;
                try {
                    $maintenance->{$method}($schemas[0]);
                } catch (ConfigurationException $exception) {
                    $error = $exception;
                }
                $results[] = $this->assertInstanceOf(ConfigurationException::class, $error, $method);
                $results[] = $this->assertEquals($originalXml, file_get_contents($xmlPath), 'XML preserved');
                $results[] = $this->assertEquals(4, (new Schema($schemas[0]))->getCapacity(), 'entries preserved');
            }
            return $results;
        });
    }

    /** @return Result[] Matching keys are removed from both replicas */
    public function deleteByTag(): array
    {
        return $this->runWithSchemas(function ($maintenance, $schemas) {
            $results = [$this->assertEquals(1, $maintenance->deleteByTag('a'))];
            foreach (array_slice($schemas, 0, 2) as $schema) {
                $results[] = $this->assertEquals(['b_c.json', 'c_d.json', 'd_e.json'], (new Schema($schema))->getAll());
            }
            return $results;
        });
    }

    /** @return Result[] Age-based removal keeps newer entries on both replicas */
    public function deleteUntil(): array
    {
        return $this->runWithSchemas(function ($maintenance, $schemas) {
            $results = [$this->assertEquals(2, $maintenance->deleteUntil(time() - strtotime('2018-02-04 04:05:06')))];
            foreach (array_slice($schemas, 0, 2) as $schema) {
                $results[] = $this->assertEquals(['c_d.json', 'd_e.json'], (new Schema($schema))->getAll());
            }
            return $results;
        });
    }

    /** @return Result[] Capacity removal keeps the newest entries on both replicas */
    public function deleteByCapacity(): array
    {
        return $this->runWithSchemas(function ($maintenance, $schemas) {
            $results = [$this->assertEquals(2, $maintenance->deleteByCapacity(2, 4))];
            foreach (array_slice($schemas, 0, 2) as $schema) {
                $results[] = $this->assertEquals(['c_d.json', 'd_e.json'], (new Schema($schema))->getAll());
            }
            return $results;
        });
    }

    /** @return Result[] Missing and malformed configuration files are rejected */
    public function rejectsInvalidFiles(): array
    {
        return $this->runWithSchemas(function ($maintenance, $schemas, $xmlPath) {
            $results = [];
            $previousErrors = libxml_use_internal_errors(true);
            try {
                foreach ([$xmlPath.'.missing', $xmlPath] as $candidate) {
                    if ($candidate === $xmlPath) {
                        file_put_contents($xmlPath, '<xml>');
                    }
                    $error = null;
                    try {
                        new DatabaseMaintenance($candidate);
                    } catch (ConfigurationException $exception) {
                        $error = $exception;
                    }
                    $results[] = $this->assertInstanceOf(ConfigurationException::class, $error);
                }
            } finally {
                libxml_clear_errors();
                libxml_use_internal_errors($previousErrors);
            }
            return $results;
        });
    }

    /** @return Result[] The environment-independent console client binds scalar arguments */
    public function consoleClient(): array
    {
        return $this->runWithSchemas(function ($maintenance, $schemas, $xmlPath) {
            $results = [];
            foreach ([
                ['checkHealth', '10.0'],
                ['plugIn', $schemas[2]],
                ['deleteByCapacity', '2', '4'],
                ['plugOut', $schemas[2]]
            ] as $arguments) {
                [$status, $stdout, $stderr] = $this->runConsole($xmlPath, $arguments);
                $response = json_decode($stdout, true, 512, JSON_THROW_ON_ERROR);
                $results[] = $this->assertEquals(0, $status, $arguments[0]);
                $results[] = $this->assertEquals('', $stderr, $arguments[0]);
                $results[] = $this->assertEquals('ok', $response['status']);
                if ($arguments[0] === 'checkHealth') {
                    $results[] = $this->assertEquals(
                        [$schemas[0] => SchemaStatus::ONLINE->value, $schemas[1] => SchemaStatus::ONLINE->value],
                        $response['body']
                    );
                } elseif ($arguments[0] === 'deleteByCapacity') {
                    $results[] = $this->assertEquals(2, $response['body']);
                } else {
                    $results[] = $this->assertTrue($response['body'] === null);
                }
            }
            foreach ([
                ['unknown'], ['getConfiguration', $xmlPath], ['__construct', $xmlPath],
                ['deleteByTag'], ['deleteUntil', 'invalid']
            ] as $arguments) {
                [$status, $stdout, $stderr] = $this->runConsole($xmlPath, $arguments);
                $response = json_decode($stderr, true, 512, JSON_THROW_ON_ERROR);
                $results[] = $this->assertEquals(1, $status, $arguments[0]);
                $results[] = $this->assertEquals('', $stdout);
                $results[] = $this->assertEquals('error', $response['status']);
            }
            return $results;
        });
    }

    /**
     * Executes the console client without shell interpolation from a different directory.
     *
     * @param string $xmlPath Absolute configuration filename
     * @param string[] $arguments Method name followed by scalar parameters
     * @return array<int,int|string> Exit status, standard output, and standard error
     */
    private function runConsole(string $xmlPath, array $arguments): array
    {
        $process = proc_open(
            [PHP_BINARY, dirname(__DIR__).'/client.php', $xmlPath, ...$arguments],
            [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
            dirname($xmlPath)
        );
        if (!is_resource($process)) {
            throw new \RuntimeException('Unable to start the console client');
        }
        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        return [proc_close($process), $stdout, $stderr];
    }

    /**
     * Creates two populated replicas and one empty candidate for one operation.
     *
     * @param callable(DatabaseMaintenance, string[], string): Result[] $test Test callback receiving maintenance, paths, and XML filename
     * @return Result[] Assertions produced by the callback
     */
    private function runWithSchemas(callable $test): array
    {
        $directory = sys_get_temp_dir().'/lucinda-db-tests-'.bin2hex(random_bytes(8));
        mkdir($directory);
        $schemas = [$directory.'/first', $directory.'/second', $directory.'/third'];
        $xmlPath = $directory.'/configuration.xml';
        try {
            foreach ($schemas as $index => $schema) {
                mkdir($schema);
                if ($index < 2) {
                    foreach ([
                        'a_b' => ['1', '2018-01-02 01:02:03'],
                        'b_c' => ['2', '2018-02-03 04:05:06'],
                        'c_d' => ['3', '2018-03-04 07:08:09'],
                        'd_e' => ['4', '2018-04-05 10:11:12']
                    ] as $key => $entry) {
                        file_put_contents($schema.'/'.$key.'.json', $entry[0]);
                        touch($schema.'/'.$key.'.json', strtotime($entry[1]));
                    }
                }
            }
            $xml = new \SimpleXMLElement('<xml><lucinda_db><schemas/></lucinda_db></xml>');
            foreach (array_slice($schemas, 0, 2) as $schema) {
                $xml->lucinda_db->schemas->addChild('schema', $schema);
            }
            $xml->asXML($xmlPath);
            return $test(new DatabaseMaintenance($xmlPath), $schemas, $xmlPath);
        } finally {
            foreach ($schemas as $schema) {
                if (is_dir($schema)) {
                    foreach (glob($schema.'/*') as $file) {
                        unlink($file);
                    }
                    rmdir($schema);
                }
            }
            if (file_exists($xmlPath)) {
                unlink($xmlPath);
            }
            rmdir($directory);
        }
    }
}
