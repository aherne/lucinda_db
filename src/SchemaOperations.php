<?php

namespace Lucinda\DB;

/**
 * Defines directory-level operations shared by single and replicated schemas
 *
 * Listings contain .json filenames, rather than decoded values or bare key stems.
 * Replicated implementations may answer queries from one selected schema. Refer
 * to each implementation for aggregation and directory-removal return semantics.
 *
 * @see Schema
 * @see SchemaDriver
 */
interface SchemaOperations
{
    /**
     * Creates the managed schema directories
     *
     * @return bool Creation result as defined by the implementation
     */
    public function create(): bool;

    /**
     * Removes the managed schema directories and their entries
     *
     * @return bool Removal result as defined by the implementation
     */
    public function drop(): bool;

    /**
     * Checks whether the managed schemas are available and writable
     *
     * @return bool Availability according to the implementation's scope
     */
    public function exists(): bool;

    /**
     * Counts .json entries in the schema directory
     *
     * @return int Number of filenames visited by the entry counter
     */
    public function getCapacity(): int;

    /**
     * Lists entry filenames in alphabetical order
     *
     * @return string[] Sorted filenames including their .json suffix
     */
    public function getAll(): array;

    /**
     * Lists entry filenames matching a complete tag segment
     *
     * @param string $tag Valid lowercase tag to match between underscores or filename boundaries
     * @return string[] Matching .json filenames in alphabetical order
     * @throws KeyException If tag validation fails
     */
    public function getByTag(string $tag): array;

    /**
     * Deletes entries in the managed schema scope
     *
     * @return int Number of logical entries counted by the implementation
     */
    public function deleteAll(): int;
}
