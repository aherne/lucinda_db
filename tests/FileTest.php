<?php

namespace Test\Lucinda\DB;

use Lucinda\DB\File;
use Test\Lucinda\DB\TestCase;
use Lucinda\DB\FileUpdater\Increment;
use Lucinda\DB\FileUpdater\IndexSaver;
use Lucinda\UnitTest\Result;

/**
 * Verifies JSON file operations and rejection of invalid update sources.
 */
final class FileTest extends TestCase
{
    /** Entry used by the ordinary write/read/update/delete lifecycle checks */
    private File $object;

    /**
     * Prepares an empty fixture path for the lifecycle checks.
     */
    public function __construct()
    {
        $schema = __DIR__."/DB";
        if (!file_exists($schema)) {
            mkdir($schema, 0777);
        }
        $this->object = new File($schema."/x_y.json");
        if ($this->object->exists()) {
            $this->object->delete();
        }
    }

    /**
     * Removes the directory after the lifecycle entry has been deleted.
     */
    public function __destruct()
    {
        rmdir(__DIR__."/DB");
    }


    /** @return Result Writing creates a JSON entry at the configured path */
    public function write(): Result
    {
        $this->object->write(["abc"=>"def"]);
        return $this->assertTrue($this->object->exists());
    }

    /** @return Result The written entry exists */
    public function exists(): Result
    {
        return $this->assertTrue($this->object->exists());
    }


    /** @return Result Reading decodes the original associative array */
    public function read(): Result
    {
        return $this->assertEquals(["abc"=>"def"], $this->object->read());
    }


    /** @return Result Updating replaces the selected mapping */
    public function update(): Result
    {
        $this->object->update(new IndexSaver("qwe", "abc"));
        return $this->assertEquals(["abc"=>"qwe"], $this->object->read());
    }


    /** @return Result[] Deletion removes the entry without a sibling lock file */
    public function delete(): array
    {
        $this->object->delete();
        return [
            $this->assertFalse($this->object->exists()),
            $this->assertFileNotExists(__DIR__."/DB/x_y.json.lock")
        ];
    }

    /**
     * Requires update callers to initialize a missing entry explicitly.
     *
     * @return Result[] Missing entries raise an exception and remain absent
     */
    public function updateRejectsMissingFile(): array
    {
        $path = tempnam(sys_get_temp_dir(), 'lucinda-missing-');
        unlink($path);
        try {
            $file = new File($path);
            $exception = null;
            try {
                $file->update(new IndexSaver('schema', 'added'));
            } catch (\RuntimeException $error) {
                $exception = $error;
            }
            $results = [
                $this->assertInstanceOf(\RuntimeException::class, $exception),
                $this->assertFileNotExists($path, 'missing entry was not recreated')
            ];
            $file->write([]);
            $file->update(new IndexSaver('schema', 'added'));
            $results[] = $this->assertEquals(['added' => 'schema'], $file->read(), 'explicit initialization permits update');
            return $results;
        } finally {
            if (file_exists($path)) {
                unlink($path);
            }
        }
    }

    /**
     * Prevents empty, whitespace-only, or malformed JSON from being overwritten.
     *
     * @return Result[] Invalid bytes remain unchanged and failures release the lock
     */
    public function updateRejectsInvalidJson(): array
    {
        $path = tempnam(sys_get_temp_dir(), 'lucinda-invalid-');
        try {
            $file = new File($path);
            $results = [];
            foreach (['', '   ', '{'] as $contents) {
                file_put_contents($path, $contents);
                $exception = null;
                try {
                    $file->update(new IndexSaver('schema', 'added'));
                } catch (\JsonException $error) {
                    $exception = $error;
                }
                $results[] = $this->assertInstanceOf(\JsonException::class, $exception);
                $results[] = $this->assertEquals($contents, file_get_contents($path), 'invalid bytes preserved');
            }
            $file->write([]);
            $file->update(new IndexSaver('schema', 'added'));
            $results[] = $this->assertEquals(['added' => 'schema'], $file->read(), 'lock released after decoding failure');
            return $results;
        } finally {
            unlink($path);
        }
    }

    /**
     * Prevents a failed read from replacing a readable-again entry with new data.
     *
     * @return Result[] Read failures preserve bytes; privileged runtimes report the unavailable fixture
     */
    public function updateRejectsUnreadableFile(): array
    {
        $path = tempnam(sys_get_temp_dir(), 'lucinda-unreadable-');
        $contents = '{"existing":"preserved"}';
        file_put_contents($path, $contents);
        try {
            chmod($path, 0000);
            clearstatcache(true, $path);
            if (is_readable($path)) {
                return [$this->assertTrue(true, 'Permission fixture unavailable: this runtime can read mode-0000 files')];
            }
            $file = new File($path);
            $exception = null;
            try {
                $file->update(new IndexSaver('schema', 'added'));
            } catch (\RuntimeException $error) {
                $exception = $error;
            }
            chmod($path, 0600);
            $results = [
                $this->assertInstanceOf(\RuntimeException::class, $exception),
                $this->assertEquals($contents, file_get_contents($path), 'unreadable entry preserved')
            ];
            $file->update(new IndexSaver('schema', 'added'));
            $results[] = $this->assertEquals(
                ['existing' => 'preserved', 'added' => 'schema'],
                $file->read(),
                'lock released after read failure'
            );
            return $results;
        } finally {
            chmod($path, 0600);
            unlink($path);
        }
    }

    /**
     * Distinguishes valid empty JSON collections and numeric zero from empty bytes.
     *
     * @return Result[] Supported JSON values reach the updater unchanged
     */
    public function updateAcceptsValidEmptyValues(): array
    {
        $path = tempnam(sys_get_temp_dir(), 'lucinda-valid-');
        try {
            $file = new File($path);
            $results = [];
            foreach (['[]', '{}'] as $contents) {
                file_put_contents($path, $contents);
                $file->update(new IndexSaver('schema', 'added'));
                $results[] = $this->assertEquals(['added' => 'schema'], $file->read());
            }
            file_put_contents($path, '0');
            $file->update(new Increment());
            $results[] = $this->assertEquals(1, $file->read(), 'numeric zero is valid JSON');
            return $results;
        } finally {
            unlink($path);
        }
    }
}
