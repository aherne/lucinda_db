<?php

namespace Lucinda\DB;

use Lucinda\DB\FileUpdater\Increment;
use Lucinda\DB\FileUpdater\Decrement;

/**
 * Encapsulates value in KV store based on schema and key
 */
class Value implements ValueOperations
{
    private string $schema;
    private string $key;

    /**
     * Constructs entry based on schema folder and tags it depends on
     *
     * @param string $schema Folder holding entries
     * @param string $key    Value of entry key
     */
    public function __construct(string $schema, string $key)
    {
        $this->schema = $schema;
        $this->key = $key;
    }

    /**
     * Sets entry value
     *
     * @param  mixed $value
     * @throws \JsonException
     */
    public function set(mixed $value): void
    {
        $file1 = new File($this->schema."/".$this->key.".json");
        $file1->write($value);
    }

    /**
     * Gets existing entry value
     *
     * @throws KeyNotFoundException If entry doesn't exist
     * @throws \JsonException
     * @return mixed
     */
    public function get(): mixed
    {
        $file = new File($this->schema."/".$this->key.".json");
        if (!$file->exists()) {
            throw new KeyNotFoundException($this->key);
        }
        return $file->read();
    }

    /**
     * Checks if entry exists
     *
     * @return bool
     */
    public function exists(): bool
    {
        $file = new File($this->schema."/".$this->key.".json");
        return $file->exists();
    }

    /**
     * Increments existing entry and returns value
     *
     * @param  int $step Step of incrementation
     * @throws KeyNotFoundException If entry doesn't exist
     * @throws \JsonException If value could not be decoded
     * @throws LockException If mutex could not be acquired.
     * @return int
     */
    public function increment(int $step = 1): int
    {
        $file = new File($this->schema."/".$this->key.".json");
        if (!$file->exists()) {
            throw new KeyNotFoundException($this->key);
        }

        $fileUpdater = new Increment($step);
        $file->update($fileUpdater);

        return $fileUpdater->getValue();
    }

    /**
     * Decrements existing entry and returns value
     *
     * @param  int $step Step of decrementation
     * @throws KeyNotFoundException If entry doesn't exist
     * @throws \JsonException If value could not be decoded
     * @throws LockException If mutex could not be acquired.
     * @return int
     */
    public function decrement(int $step = 1): int
    {
        $file = new File($this->schema."/".$this->key.".json");
        if (!$file->exists()) {
            throw new KeyNotFoundException($this->key);
        }

        $fileUpdater = new Decrement($step);
        $file->update($fileUpdater);

        return $fileUpdater->getValue();
    }

    /**
     * Deletes existing entry
     *
     * @throws KeyNotFoundException If entry doesn't exist
     */
    public function delete(): void
    {
        $file = new File($this->schema."/".$this->key.".json");
        if (!$file->exists()) {
            throw new KeyNotFoundException($this->key);
        }
        $file->delete();
    }
}
