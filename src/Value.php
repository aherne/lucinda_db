<?php

namespace Lucinda\DB;

use Lucinda\DB\FileUpdater\Increment;
use Lucinda\DB\FileUpdater\Decrement;

/**
 * Operates on one key inside one schema folder
 *
 * The entry path is schema/key.json. JSON encoding, decoding, atomic replacement,
 * and mutation locking are delegated to File. Read, counter, and deletion methods
 * require an existing entry; set() may create it. Counter operations expect an
 * integer-compatible stored value and do not validate arbitrary payload types.
 *
 * @see Key
 * @see File
 * @see ValueOperations
 */
final class Value implements ValueOperations
{
    /**
     * Directory holding this entry
     */
    private string $schema;

    /**
     * Filename stem used inside the schema folder
     */
    private string $key;

    /**
     * Binds entry operations to a schema folder and filename stem
     *
     * The supplied key is used directly; use Key or Wrapper when deriving it from tags.
     *
     * @param string $schema Existing writable schema directory
     * @param string $key Filename stem without the .json suffix
     */
    public function __construct(string $schema, string $key)
    {
        $this->schema = $schema;
        $this->key = $key;
    }

    /**
     * Creates or replaces the entry's JSON value
     *
     * @param mixed $value JSON-encodable payload
     * @throws \JsonException If encoding fails
     * @throws LockException If the entry mutex cannot be acquired
     * @throws \RuntimeException If replacing the entry file fails
     */
    public function set(mixed $value): void
    {
        $file1 = new File($this->schema."/".$this->key.".json");
        $file1->write($value);
    }

    /**
     * Reads and decodes an existing entry
     *
     * @return mixed Stored JSON value, with objects decoded as associative arrays
     * @throws KeyNotFoundException If the entry file does not exist
     * @throws \JsonException If the stored contents cannot be decoded
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
     * Checks whether this schema contains the entry file
     *
     * @return bool True when the entry path exists
     */
    public function exists(): bool
    {
        $file = new File($this->schema."/".$this->key.".json");
        return $file->exists();
    }

    /**
     * Increments an existing integer value under its exclusive mutex
     *
     * @param int $step Amount to add, defaulting to one
     * @return int Value produced by the updater
     * @throws KeyNotFoundException If the entry does not exist
     * @throws \JsonException If reading or encoding fails
     * @throws LockException If the entry mutex cannot be acquired
     * @throws \RuntimeException If the updated file cannot be replaced
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
     * Decrements an existing integer value under its exclusive mutex
     *
     * @param int $step Amount to subtract, defaulting to one
     * @return int Value produced by the updater
     * @throws KeyNotFoundException If the entry does not exist
     * @throws \JsonException If reading or encoding fails
     * @throws LockException If the entry mutex cannot be acquired
     * @throws \RuntimeException If the updated file cannot be replaced
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
     * Deletes an existing entry while holding its file mutex
     *
     * @throws KeyNotFoundException If the entry does not exist
     * @throws LockException If the entry mutex cannot be acquired
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
