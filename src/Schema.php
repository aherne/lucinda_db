<?php
namespace Lucinda\DB;

use Lucinda\DB\FileDeleter\All as DeleteAll;
use Lucinda\DB\FileDeleter\ByTag as DeleteTag;
use Lucinda\DB\FileInspector\Counter;
use Lucinda\DB\FileInspector\All as InspectAll;
use Lucinda\DB\FileInspector\ByTag as InspectTag;

/**
 * Encapsulates operations on a LucindaDB folder schema [4,294,967,295]
 */
class Schema implements SchemaOperations
{
    private $path;
    
    /**
     * Constructs based on schema folder
     *
     * @param string $schema
     */
    public function __construct(string $schema)
    {
        $this->path = $schema;
    }
    
    /**
     * Creates schema on disk
     */
    public function create(): bool
    {
        $folder = new Folder($this->path);
        return $folder->create(0777);
    }
    
    /**
     * Checks if schema exists and it is writable
     *
     * @return boolean
     */
    public function exists(): bool
    {
        $object = new Folder($this->path);
        return ($object->exists() && $object->isWritable());
    }
    
    /**
     * Gets number of entries in schema
     *
     * @return int Number of entries (keys) found
     */
    public function getCapacity(): int
    {
        $counter = new Counter();
        $folder = new Folder($this->path);
        $folder->scan($counter);
        return $counter->getValue();
    }
    
    /**
     * Gets all keys in schema
     *
     * @return string[] List of keys found in schema
     */
    public function getAll(): array
    {
        $all = new InspectAll();
        $folder = new Folder($this->path);
        $folder->scan($all);
        return $all->getEntries();
    }
    
    /**
     * Gets all keys in schema matching tag
     *
     * @param string $tag Tag name
     * @return string[] List of keys found in schema
     */
    public function getByTag(string $tag): array
    {
        $all = new InspectTag($tag);
        $folder = new Folder($this->path);
        $folder->scan($all);
        return $all->getEntries();
    }
    
    /**
     * Deletes all entries in schema
     *
     * @return int Number of entries deleted
     */
    public function deleteAll(): int
    {
        $folder = new Folder($this->path);
        return $folder->clear(new DeleteAll());
    }
    
    /**
     * Deletes entries from schema by tag
     *
     * @param string $tag Tag name
     * @return int Number of entries deleted
     */
    public function deleteByTag(string $tag): int
    {
        $folder = new Folder($this->path);
        return $folder->clear(new DeleteTag($tag));
    }
    
    /**
     * Drops schema from disk
     */
    public function drop(): void
    {
        $folder = new Folder($this->path);
        $folder->clear(new DeleteAll());
        $folder->delete();
    }
}
