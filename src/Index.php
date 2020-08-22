<?php
namespace Lucinda\DB;

use Lucinda\DB\FileUpdater\IndexDeleter;
use Lucinda\DB\FileUpdater\IndexSaver;

/**
 * Encapsulates an index in KV store based on schema and tag
 */
class Index
{
    private $schema;
    private $tag;
    
    /**
     * Constructs index based on schema folder and tag
     *
     * @param string $schema Folder holding indexes
     * @param string $tag Value of tag
     * @throws KeyException If tags contain non-alphanumeric characters
     */
    public function __construct(string $schema, string $tag)
    {
        $this->schema = $schema;
        $this->tag = $tag;
    }
        
    /**
     * Checks if index exists
     *
     * @return bool
     */
    public function exists(): bool
    {
        $file = new File($this->schema."/".$this->tag.".json");
        return $file->exists();
    }
    
    /**
     * Adds key to index
     * 
     * @param string $key
     */
    public function add(string $key): void
    {
        $file = new File($this->schema."/".$this->tag.".json");
        if (!$file->exists()) {
            $file->write([$this->tag=>$this->tag]);
        } else {
            $file->update(new IndexSaver($key));
        }
    }
    
    /**
     * Removes key from index
     * 
     * @param string $key
     */
    public function remove(string $key): void
    {
        $file = new File($this->schema."/".$this->tag.".json");
        if (!$file->exists()) {
            throw new IndexNotFoundException($this->tag);
        }
        $file->update(new IndexDeleter($key));
    }
    
    /**
     * Gets all keys in index
     * 
     * @throws IndexNotFoundException
     * @return array
     */
    public function get(): array
    {
        $file = new File($this->schema."/".$this->tag.".json");
        if (!$file->exists()) {
            throw new IndexNotFoundException($this->tag);
        }
        return $file->read();
    }
    
    /**
     * Deletes existing index
     *
     * @throws KeyNotFoundException If entry doesn't exist
     */
    public function delete(): void
    {
        $file = new File($this->schema."/".$this->tag.".json");
        if (!$file->exists()) {
            throw new IndexNotFoundException($this->tag);
        }
        $file->delete();
    }
}
