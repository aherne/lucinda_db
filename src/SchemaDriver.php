<?php
namespace Lucinda\DB;

use Lucinda\DB\FileDeleter\All as DeleteAll;
use Lucinda\DB\FileDeleter\ByTag as DeleteTag;
use Lucinda\DB\FileDeleter\ByModifiedTime as DeleteByModifiedTime;
use Lucinda\DB\FileDeleter\ByCapacity as DeleteByCapacity;
use Lucinda\DB\FileInspector\Importer;

class SchemaDriver implements SchemaOperations
{
    private $schemas = [];
    
    /**
     * Sets up database entry to query based on Configuration object and tags key is composed of
     *
     * @param Configuration $configuration
     */
    public function __construct(Configuration $configuration)
    {
        $this->schemas = $configuration->getSchemas();
    }
        
    /**
     * Creates schemas on disk
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
     * Checks if schemas exists and it is writable
     *
     * @return boolean
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
     * Gets number of entries in schema
     *
     * @return int Number of entries (keys) found
     */
    public function getCapacity(): int
    {
        $schema = new Schema($this->schemas[rand(0, sizeof($this->schemas)-1)]);
        return $schema->getCapacity();
    }
    
    /**
     * Gets all keys in schema
     *
     * @return string[] List of keys found in schema
     */
    public function getAll(): array
    {
        $schema = new Schema($this->schemas[rand(0, sizeof($this->schemas)-1)]);
        return $schema->getAll();
    }
    
    /**
     * Gets all keys in schema matching tag
     *
     * @param string $tag Tag name
     * @return string[] List of keys found in schema
     */
    public function getByTag(string $tag): array
    {
        $schema = new Schema($this->schemas[rand(0, sizeof($this->schemas)-1)]);
        return $schema->getByTag($tag);
    }
    
    /**
     * Deletes all entries in schema
     *
     * @return int Number of entries deleted
     */
    public function deleteAll(): int
    {
        $folder = new Folder($this->schemas[rand(0, sizeof($this->schemas)-1)]);
        return $folder->clear(new DeleteAll($this->schemas));
    }
    
    /**
     * Deletes entries from schema by tag
     *
     * @param string $tag Tag name
     * @return int Number of entries deleted
     */
    public function deleteByTag(string $tag): int
    {
        $folder = new Folder($this->schemas[rand(0, sizeof($this->schemas)-1)]);
        return $folder->clear(new DeleteTag($tag, $this->schemas));
    }
    
    /**
     * Deletes entries in schema whose last modified time is earlier than input
     *
     * @param int $startTime Unix time starting whom entries won't be deleted
     * @return int Number of entries deleted
     */
    public function deleteUntil(int $startTime): int
    {
        $folder = new Folder($this->schemas[rand(0, sizeof($this->schemas)-1)]);
        return $folder->clear(new DeleteByModifiedTime($startTime, $this->schemas));
    }
    
    /**
     * Deletes entries from schema exceeding maximum capacity based on last modified time.
     *
     * @param int $minCapacity Number of entries allowed to remain if schema reaches max capacity
     * @param int $maxCapacity Maximum number of entries allowed to exist in schema
     * @return int Number of entries deleted
     */
    public function deleteByCapacity(int $minCapacity, int $maxCapacity): int
    {
        $fileDeleter = new DeleteByCapacity($this->schemas, $minCapacity, $maxCapacity);
        $folder = new Folder($this->schemas[rand(0, sizeof($this->schemas)-1)]);
        $folder->clear($fileDeleter);
        return $fileDeleter->getTotal();
    }
    
    /**
     * Plugs in a new schema and populates it based on first replica
     * 
     * @param string $destinationSchema
     */
    public function plugIn(string $destinationSchema): void
    {
        $folder = new Folder($this->schemas[0]);
        $folder->scan(new Importer($destinationSchema));
    }
    
    /**
     * Creates schema on disk
     */
    public function drop(): void
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
