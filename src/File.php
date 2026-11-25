<?php

namespace Lucinda\DB;

/**
 * Reads and mutates one JSON file using a separate process-shared mutex
 *
 * Reads decode JSON into PHP arrays and scalar values without locking. Mutations
 * acquire a nonblocking exclusive flock on a temporary-directory lock file derived
 * from the supplied path string. Contention raises LockException immediately.
 *
 * Writes encode first, create a temporary file beside the destination, then rename
 * it into place. update() holds the mutex across read, callback, and replacement.
 * The mutex file is retained after release. Callers must use consistent path
 * spellings to coordinate access to the same physical file.
 *
 * @see FileUpdater
 * @see LockException
 */
final class File
{
    /**
     * Filesystem path to the JSON entry
     */
    private string $path;

    /**
     * Stable mutex filename derived from the supplied entry path
     */
    private string $lockPath;

    /**
     * Binds JSON operations and their mutex to a filesystem path
     *
     * Construction does not create the file or its parent directory.
     *
     * @param string $path Entry filename; consistent absolute paths are recommended
     */
    public function __construct(string $path)
    {
        $this->path = $path;
        $this->lockPath = sys_get_temp_dir().DIRECTORY_SEPARATOR."lucinda_db_".sha1($path).".lock";
    }

    /**
     * Encodes and replaces the entry while holding its exclusive mutex
     *
     * @param mixed $value JSON-encodable PHP value
     * @throws \JsonException If encoding fails
     * @throws LockException If the mutex cannot be opened or acquired
     * @throws \RuntimeException If creating, writing, or replacing the temporary file fails
     */
    public function write(mixed $value): void
    {
        $json = json_encode($value, JSON_THROW_ON_ERROR);
        $this->withLock(function () use ($json) {
            $this->writeEncoded($json);
        });
    }

    /**
     * Checks whether the supplied path currently exists
     *
     * @return bool True when PHP reports an existing filesystem path
     */
    public function exists(): bool
    {
        return file_exists($this->path);
    }

    /**
     * Reads JSON without taking the mutation mutex
     *
     * JSON objects decode as associative arrays. Callers should check existence first;
     * missing or unreadable files may also produce filesystem warnings.
     *
     * @return mixed Decoded JSON value
     * @throws \JsonException If file contents are not valid JSON
     */
    public function read(): mixed
    {
        return json_decode(file_get_contents($this->path), true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * Runs an updater against the decoded value under one exclusive mutex
     *
     * Requires an existing, readable file containing valid JSON. Missing or
     * unreadable files raise an I/O exception; empty or malformed contents raise
     * a JSON exception. Validation runs under the mutex before the callback.
     * The callback receives the value by reference; replacement occurs only if
     * it returns true. A false result leaves the stored bytes untouched.
     *
     * @param FileUpdater $callback Algorithm deciding and applying the value change
     * @throws \JsonException If existing data cannot be decoded or the result encoded
     * @throws LockException If the mutex cannot be opened or acquired
     * @throws \RuntimeException If reading the existing file or writing the changed value fails
     */
    public function update(FileUpdater $callback): void
    {
        $this->withLock(function () use ($callback) {
            $contents = @file_get_contents($this->path);
            if ($contents === false) {
                throw new \RuntimeException("Could not read file for update: ".$this->path);
            }
            $json = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
            $changed = $callback->update($json);
            if ($changed) {
                $this->writeEncoded(json_encode($json, JSON_THROW_ON_ERROR));
            }
        });
    }

    /**
     * Deletes the entry if present while holding its exclusive mutex
     *
     * Deleting an absent file is harmless. The separate mutex file remains on disk.
     *
     * @throws LockException If the mutex cannot be opened or acquired
     */
    public function delete(): void
    {
        $this->withLock(function () {
            if ($this->exists()) {
                unlink($this->path);
            }
        });
    }

    /**
     * Executes a callback while owning a nonblocking exclusive flock
     *
     * Releases the lock and closes its handle even when the callback throws.
     *
     * @param callable(): void $callback Mutation to execute inside the critical section
     * @throws LockException If opening or acquiring the mutex fails
     * @throws \Throwable If the callback fails
     */
    private function withLock(callable $callback): void
    {
        $handle = fopen($this->lockPath, "c");
        if ($handle === false) {
            throw new LockException("Could not open lock: ".$this->lockPath);
        }

        try {
            if (!flock($handle, LOCK_EX | LOCK_NB)) {
                throw new LockException("Lock already active on: ".$this->path);
            }

            try {
                $callback();
            } finally {
                fflush($handle);
                flock($handle, LOCK_UN);
            }
        } finally {
            fclose($handle);
        }
    }

    /**
     * Replaces the destination using a temporary file in the same directory
     *
     * The caller must already hold the mutex. Temporary files are removed on failure;
     * renaming provides replacement visibility without exposing an intermediate JSON
     * payload to readers. This does not guarantee durability across a system crash.
     *
     * @param string $json Complete JSON text already encoded by the caller
     * @throws \RuntimeException If temporary-file creation, writing, or replacement fails
     */
    private function writeEncoded(string $json): void
    {
        $directory = dirname($this->path);
        $temporaryPath = tempnam($directory, basename($this->path).".tmp.");
        if ($temporaryPath === false) {
            throw new \RuntimeException("Could not create temporary file in: ".$directory);
        }

        try {
            $handle = fopen($temporaryPath, "wb");
            if ($handle === false) {
                throw new \RuntimeException("Could not open temporary file: ".$temporaryPath);
            }

            try {
                if (fwrite($handle, $json) === false) {
                    throw new \RuntimeException("Could not write temporary file: ".$temporaryPath);
                }
                fflush($handle);
            } finally {
                fclose($handle);
            }

            if (!rename($temporaryPath, $this->path)) {
                throw new \RuntimeException("Could not replace file: ".$this->path);
            }
        } finally {
            if (file_exists($temporaryPath)) {
                unlink($temporaryPath);
            }
        }
    }
}
