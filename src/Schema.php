<?php

namespace Lucinda\DB;

use Lucinda\DB\FileDeleter\All as DeleteAll;
use Lucinda\DB\FileDeleter\ByTag as DeleteTag;
use Lucinda\DB\FileInspector\Counter;
use Lucinda\DB\FileInspector\All as InspectAll;
use Lucinda\DB\FileInspector\ByTag as InspectTag;
use Lucinda\DB\FileInspector\Importer;

/**
 * Performs filesystem operations on a single database replica directory
 *
 * Entry scans consider filenames ending in .json. Listing and counting do not
 * read their payloads. Deletion callbacks remove entries directly, without File's
 * mutex, while population copies missing files and preserves modification times.
 * Callers must provide readable/writable directories as appropriate.
 *
 * @see Folder
 * @see SchemaOperations
 */
final class Schema implements SchemaOperations
{
    /**
     * Path to this schema directory
     */
    private string $path;

    /**
     * Captures the schema folder on which later operations act
     *
     * @param string $schema Schema directory path, retained without normalization
     */
    public function __construct(string $schema)
    {
        $this->path = $schema;
    }

    /**
     * Creates the schema directory with requested mode 0777
     *
     * The process umask can reduce permissions. Parent directories must already exist.
     *
     * @return bool Result of the directory-creation operation
     */
    public function create(): bool
    {
        $folder = new Folder($this->path);
        return $folder->create(0777);
    }

    /**
     * Checks whether the schema is a writable directory
     *
     * @return bool True when the directory exists and PHP considers it writable
     */
    public function exists(): bool
    {
        $object = new Folder($this->path);
        return ($object->exists() && $object->isWritable());
    }

    /**
     * Counts .json entries in the schema directory
     *
     * @return int Number of filenames visited by the entry counter
     */
    public function getCapacity(): int
    {
        $counter = new Counter();
        $folder = new Folder($this->path);
        $folder->scan($counter);
        return $counter->getValue();
    }

    /**
     * Lists entry filenames in alphabetical order
     *
     * @return string[] Sorted filenames including their .json suffix
     */
    public function getAll(): array
    {
        $all = new InspectAll();
        $folder = new Folder($this->path);
        $folder->scan($all);
        $result = $all->getEntries();
        sort($result);
        return $result;
    }

    /**
     * Lists entry filenames matching a complete tag segment
     *
     * @param string $tag Valid lowercase tag to match between underscores or filename boundaries
     * @return string[] Matching .json filenames in alphabetical order
     * @throws KeyException If tag validation fails
     */
    public function getByTag(string $tag): array
    {
        $all = new InspectTag($tag);
        $folder = new Folder($this->path);
        $folder->scan($all);
        $result = $all->getEntries();
        sort($result);
        return $result;
    }

    /**
     * Deletes every scanned database entry in this schema
     *
     * The schema directory remains. Legacy sibling .lock files are also removed.
     *
     * @return int Number of logical entry filenames removed
     */
    public function deleteAll(): int
    {
        $folder = new Folder($this->path);
        return $folder->clear(new DeleteAll());
    }

    /**
     * Copies missing entries from another schema while preserving timestamps
     *
     * Existing destination filenames are skipped, even when their values differ.
     * This copy does not use entry mutexes and does not overwrite existing replicas.
     *
     * @param string $sourceSchema Readable source directory containing .json entries
     */
    public function populate(string $sourceSchema): void
    {
        $folder = new Folder($sourceSchema);
        $folder->scan(new Importer($this->path));
    }

    /**
     * Clears entries and removes the schema directory
     *
     * Folder deletion also removes other immediate regular files. Subdirectories are
     * not recursively removed. The returned value is the post-deletion exists() result.
     *
     * @return bool False after successful removal; true if the folder still exists
     */
    public function drop(): bool
    {
        $folder = new Folder($this->path);
        $folder->clear(new DeleteAll());
        $folder->delete();
        return $folder->exists();
    }
}
