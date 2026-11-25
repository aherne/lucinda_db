<?php

namespace Lucinda\DB;

use Lucinda\DB\FileDeleter\All as DeleteAll;

/**
 * Coordinates schema operations over a captured set of replica directories
 *
 * Creation, existence checks, and dropping visit every configured schema. Listing,
 * counting, and tag queries use one randomly chosen replica. deleteAll() scans one
 * replica and deletes those filenames from all replicas, counting logical entries.
 * These operations assume compatible replica contents and are not transactional.
 *
 * @see Schema
 * @see SchemaOperations
 */
final class SchemaDriver implements SchemaOperations
{
    /**
     * @var string[] Replica paths used by schema operations
     */
    private array $schemas = [];

    /**
     * Captures the list of schema folders to coordinate
     *
     * @param string[] $schemas Nonempty replica list indexed consecutively from zero
     */
    public function __construct(array $schemas)
    {
        $this->schemas = $schemas;
    }

    /**
     * Attempts to create each unavailable schema directory
     *
     * An already existing writable schema makes the aggregate result false. Operations
     * on later schemas still run. Individual mkdir() results are not aggregated;
     * check exists() afterwards when availability must be confirmed.
     *
     * @return bool True when every schema needed a creation attempt
     */
    public function create(): bool
    {
        $result = true;
        foreach ($this->schemas as $schema) {
            $schema = new Schema($schema);
            if (!$schema->exists()) {
                $schema->create();
            } else {
                $result = false;
            }
        }
        return $result;
    }

    /**
     * Checks whether every configured schema is a writable directory
     *
     * @return bool True if all configured schemas satisfy Schema::exists()
     */
    public function exists(): bool
    {
        foreach ($this->schemas as $schema) {
            $schema = new Schema($schema);
            if (!$schema->exists()) {
                return false;
            }
        }
        return true;
    }

    /**
     * Counts entries in one randomly selected replica
     *
     * Assumes replicas are consistent; does not aggregate counts across folders.
     *
     * @return int Number of .json entries in the selected schema
     */
    public function getCapacity(): int
    {
        $schema = new Schema($this->schemas[rand(0, sizeof($this->schemas)-1)]);
        return $schema->getCapacity();
    }

    /**
     * Lists entries from one randomly selected replica
     *
     * @return string[] Alphabetically sorted .json filenames from the selected schema
     */
    public function getAll(): array
    {
        $schema = new Schema($this->schemas[rand(0, sizeof($this->schemas)-1)]);
        return $schema->getAll();
    }

    /**
     * Lists matching entries from one randomly selected replica
     *
     * @param string $tag Valid lowercase tag matched as a complete key segment
     * @return string[] Sorted matching .json filenames
     * @throws KeyException If tag validation fails
     */
    public function getByTag(string $tag): array
    {
        $schema = new Schema($this->schemas[rand(0, sizeof($this->schemas)-1)]);
        return $schema->getByTag($tag);
    }

    /**
     * Scans one replica and removes its entry filenames from every replica
     *
     * Files present only in other replicas are not discovered by this scan.
     *
     * @return int Number of logical entries discovered in the selected schema
     */
    public function deleteAll(): int
    {
        $folder = new Folder($this->schemas[rand(0, sizeof($this->schemas)-1)]);
        return $folder->clear(new DeleteAll($this->schemas));
    }

    /**
     * Drops each existing writable schema directory
     *
     * An already missing or unwritable schema makes the aggregate result false.
     * Individual Schema::drop() results are not used in the returned value.
     *
     * @return bool True when every schema satisfied exists() before its drop attempt
     */
    public function drop(): bool
    {
        $result = true;
        foreach ($this->schemas as $schema) {
            $schema = new Schema($schema);
            if ($schema->exists()) {
                $schema->drop();
            } else {
                $result = false;
            }
        }
        return $result;
    }
}
