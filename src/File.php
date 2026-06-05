<?php

namespace Lucinda\DB;

/**
 * Encapsulates a json file and its operations
 */
final class File
{
    private string $path;
    private string $lockPath;

    /**
     * Constructs file by absolute path.
     *
     * @param string $path Absolute path.
     */
    public function __construct(string $path)
    {
        $this->path = $path;
        $this->lockPath = sys_get_temp_dir().DIRECTORY_SEPARATOR."lucinda_db_".sha1($path).".lock";
    }

    /**
     * Writes file to disk
     *
     * @param  mixed $value Value to save as JSON
     * @throws \JsonException If value could not be encoded
     * @throws LockException If mutex could not be acquired.
     */
    public function write(mixed $value): void
    {
        $json = json_encode($value, JSON_THROW_ON_ERROR);
        $this->withLock(function () use ($json) {
            $this->writeEncoded($json);
        });
    }

    /**
     * Checks if file exists
     *
     * @return bool
     */
    public function exists(): bool
    {
        return file_exists($this->path);
    }

    /**
     * Reads file and returns value
     *
     * @return mixed
     * @throws \JsonException If value could not be decoded
     */
    public function read(): mixed
    {
        return json_decode(file_get_contents($this->path), true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * Updates value in file by callback using exclusive lock to insure writes synchronization
     *
     * WARNING: this operation is protected by a mutex, but in order to to prevent deadlocks it doesn't wait
     * if mutex could not be acquired
     *
     * @param  FileUpdater $callback Encapsulates algorithm of file value update
     * @throws \JsonException If value could not be decoded
     * @throws LockException If mutex could not be acquired.
     */
    public function update(FileUpdater $callback): void
    {
        $this->withLock(function () use ($callback) {
            $json = [];
            if ($this->exists()) {
                $contents = file_get_contents($this->path);
                if ($contents !== false && $contents !== "") {
                    $json = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
                }
            }
            $changed = $callback->update($json);
            if ($changed) {
                $this->writeEncoded(json_encode($json, JSON_THROW_ON_ERROR));
            }
        });
    }

    /**
     * Deletes file from disk
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
     * Runs a callback while holding the file mutex.
     *
     * @param callable $callback
     * @throws LockException If mutex could not be acquired.
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
     * Writes already encoded JSON atomically.
     *
     * @param string $json
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
