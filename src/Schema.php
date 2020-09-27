<?php
namespace Lucinda\DB;

use Lucinda\DB\FileDeleter\All as DeleteAll;
use Lucinda\DB\FileDeleter\ByTag as DeleteTag;
use Lucinda\DB\FileInspector\Counter;
use Lucinda\DB\FileInspector\All as InspectAll;
use Lucinda\DB\FileInspector\ByTag as InspectTag;
use Lucinda\DB\FileInspector\Importer;

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
     * Gets all keys in schema sorted alphabetically
     *
     * @return string[] List of keys found in schema
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
     * Gets all keys in schema matching tag sorted alphabetically
     *
     * @param string $tag Tag name
     * @return string[] List of keys found in schema
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
     * Populates schema based on another one
     *
     * @param string $sourceSchema
     */
    public function populate(string $sourceSchema): void
    {
        $folder = new Folder($sourceSchema);
        $folder->scan(new Importer($this->path));
    }
    
    /**
     * Drops schema from disk
     *
     * @return boolean
     */
    public function drop(): bool
    {
        $folder = new Folder($this->path);
        $folder->clear(new DeleteAll());
        $folder->delete();
        return $folder->exists();
    }
}
