<?php
namespace Lucinda\DB;

use Lucinda\DB\FileUpdater\Increment;
use Lucinda\DB\FileUpdater\Decrement;

/**
 * Encapsulates value in KV store based on schema and key
 */
class Value
{
    private $schema;
    private $key;
    
    /**
     * Constructs entry based on schema folder and tags it depends on
     *
     * @param string $schema Folder holding entries
     * @param string $key Value of entry key
     */
    public function __construct(string $schema, string $key)
    {
        $this->schema = $schema;
        $this->key = $key;
    }
    
    /**
     * Sets entry value
     *
     * @param mixed $value
     */
    public function set($value): void
    {
        $file1 = new File($this->schema."/".$this->key.".json");
        $file1->write($value);
    }
    
    /**
     * Gets existing entry value
     *
     * @throws KeyNotFoundException If entry doesn't exist
     * @return mixed
     */
    public function get()
    {
        $file = new File($this->schema."/".$this->key.".json");
        if (!$file->exists()) {
            throw new KeyNotFoundException($this->key);
        }
        return $file->read();
    }
    
    /**
     * Checks if entry exists
     *
     * @return bool
     */
    public function exists(): bool
    {
        $file = new File($this->schema."/".$this->key.".json");
        return $file->exists();
    }
    
    /**
     * Increments existing entry and returns value
     *
     * @throws KeyNotFoundException If entry doesn't exist
     * @return int
     */
    public function increment(): int
    {
        $file = new File($this->schema."/".$this->key.".json");
        if (!$file->exists()) {
            throw new KeyNotFoundException($this->key);
        }
        
        $fileUpdater = new Increment($this->schema."/".$this->key.".json");
        $file->update($fileUpdater);
        
        return $fileUpdater->getValue();
    }
    
    /**
     * Decrements existing entry and returns value
     *
     * @throws KeyNotFoundException If entry doesn't exist
     * @return int
     */
    public function decrement(): int
    {
        $file = new File($this->schema."/".$this->key.".json");
        if (!$file->exists()) {
            throw new KeyNotFoundException($this->key);
        }
        
        $fileUpdater = new Decrement($this->schema."/".$this->key.".json");
        $file->update($fileUpdater);
        
        return $fileUpdater->getValue();
    }
    
    /**
     * Deletes existing entry
     *
     * @throws KeyNotFoundException If entry doesn't exist
     */
    public function delete(): void
    {
        $file = new File($this->schema."/".$this->key.".json");
        if (!$file->exists()) {
            throw new KeyNotFoundException($this->key);
        }
        $file->delete();
    }
}
