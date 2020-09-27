<?php
namespace Lucinda\DB;

use Lucinda\DB\FileDeleter\All as DeleteAll;

class SchemaDriver implements SchemaOperations
{
    private $configuration;
    private $schemas = [];
    
    /**
     * Sets up database entry to query based on Configuration object and tags key is composed of
     *
     * @param Configuration $configuration
     */
    public function __construct(Configuration $configuration)
    {
        $this->configuration = $configuration;
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
     * Gets all keys in schema sorted alphabetically
     *
     * @return string[] List of keys found in schema
     */
    public function getAll(): array
    {
        $schema = new Schema($this->schemas[rand(0, sizeof($this->schemas)-1)]);
        return $schema->getAll();
    }
    
    /**
     * Gets all keys in schema matching tag sorted alphabetically
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
     * Creates schema on disk
     *
     * @return boolean
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
