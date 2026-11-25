<?php

namespace Lucinda\DB;

/**
 * Defines JSON value operations for one database key
 *
 * Implementations bind the key and storage scope during construction. A replicated
 * driver may read any healthy copy and write several files. Counter operations
 * expect integer-compatible values and return the updated integer.
 *
 * @see Value
 * @see ValueDriver
 */
interface ValueOperations
{
    /**
     * Creates or replaces the entry's JSON value
     *
     * @param mixed $value JSON-encodable payload
     * @throws \JsonException If encoding fails
     * @throws LockException If the entry mutex cannot be acquired
     * @throws \RuntimeException If replacing the entry file fails
     */
    public function set(mixed $value): void;

    /**
     * Reads and decodes an existing entry
     *
     * @return mixed Stored JSON value, with objects decoded as associative arrays
     * @throws KeyNotFoundException If the entry file does not exist
     * @throws \JsonException If the stored contents cannot be decoded
     */
    public function get(): mixed;

    /**
     * Checks whether an entry is present within the implementation's scope
     *
     * @return bool True when an entry file is present
     */
    public function exists(): bool;

    /**
     * Adds a step to an existing integer-compatible value
     *
     * @param int $step Amount to add, defaulting to one
     * @return int Updated counter value
     * @throws KeyNotFoundException If the required entry does not exist
     * @throws \JsonException If JSON conversion fails
     * @throws LockException If a mutation lock cannot be acquired
     */
    public function increment(int $step = 1): int;

    /**
     * Subtracts a step from an existing integer-compatible value
     *
     * @param int $step Amount to subtract, defaulting to one
     * @return int Updated counter value
     * @throws KeyNotFoundException If the required entry does not exist
     * @throws \JsonException If JSON conversion fails
     * @throws LockException If a mutation lock cannot be acquired
     */
    public function decrement(int $step = 1): int;

    /**
     * Deletes an existing entry while holding its file mutex
     *
     * @throws KeyNotFoundException If the entry does not exist
     * @throws LockException If the entry mutex cannot be acquired
     */
    public function delete(): void;
}
