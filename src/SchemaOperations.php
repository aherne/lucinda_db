<?php

namespace Lucinda\DB;

interface SchemaOperations
{
    /**
     * Creates schema on disk
     */
    public function create(): bool;

    /**
     * Drops schema from disk
     *
     * @return boolean
     */
    public function drop(): bool;

    /**
     * Checks if schema exists and it is writable
     *
     * @return boolean
     */
    public function exists(): bool;

    /**
     * Gets number of entries in schema
     *
     * @return int Number of entries (keys) found
     */
    public function getCapacity(): int;

    /**
     * Gets all keys in schema sorted alphabetically
     *
     * @return string[] List of keys found in schema
     */
    public function getAll(): array;

    /**
     * Gets all keys in schema matching tag sorted alphabetically
     *
     * @param  string $tag Tag name
     * @return string[] List of keys found in schema
     */
    public function getByTag(string $tag): array;

    /**
     * Deletes all entries in schema
     *
     * @return int Number of entries deleted
     */
    public function deleteAll(): int;
}
