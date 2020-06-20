<?php
namespace Lucinda\DB;

use Lucinda\DB\FileUpdater\Increment;
use Lucinda\DB\FileUpdater\Decrement;

/**
 * Encapsulates operations on an entry in LucindaDB
 */
class DatabaseEntry
{
    private $keysFolder;
    private $key;
    
    /**
     * Constructs entry based on schema folder and tags it depends on
     * 
     * @param string $schema Folder holding entries
     * @param array $tags List of tags entry depends on.
     * @throws KeyException If tags contain non-alphanumeric characters
     */
    public function __construct(string $schema, array $tags)
    {
        // saves folder
        $this->keysFolder = $schema;
                
        // builds key
        $object = new Key($tags);
        $this->key = $object->getValue();
    }
    
    /**
     * Sets entry value
     * 
     * @param mixed $value
     */
    public function set($value): void
    {
        $file1 = new File($this->keysFolder."/".$this->key.".json");
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
        $file = new File($this->keysFolder."/".$this->key.".json");
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
        $file = new File($this->keysFolder."/".$this->key.".json");
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
        $file = new File($this->keysFolder."/".$this->key.".json");
        if (!$file->exists()) {
            throw new KeyNotFoundException($this->key);
        }
        
        $fileUpdater = new Increment($this->keysFolder."/".$this->key.".json");
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
        $file = new File($this->keysFolder."/".$this->key.".json");
        if (!$file->exists()) {
            throw new KeyNotFoundException($this->key);
        }
        
        $fileUpdater = new Decrement($this->keysFolder."/".$this->key.".json");
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
        $file = new File($this->keysFolder."/".$this->key.".json");
        if (!$file->exists()) {
            throw new KeyNotFoundException($this->key);
        }
        $file->delete();
    }
}