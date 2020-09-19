<?php
namespace Lucinda\DB;

/**
 * Wrapper of Value for data distribution, allowing traffic leveraging on multiple disks
 */
class ValueDriver implements ValueOperations
{
    private $schemas = [];
    private $key;
    
    /**
     * Sets up database entry to query based on Configuration object and tags key is composed of
     *
     * @param Configuration $configuration
     * @param array $tags List of tags key is composed of
     */
    public function __construct(Configuration $configuration, array $tags)
    {
        $this->schemas = $configuration->getSchemas();
        
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
        foreach($this->schemas as $schema) {
            $object = new Value($schema, $this->key);
            $object->set($value);
        }
    }
    
    /**
     * Gets existing entry value
     *
     * @throws KeyNotFoundException If entry doesn't exist
     * @return mixed
     */
    public function get()
    {
        $object = new Value($this->schemas[rand(0, sizeof($this->schemas)-1)], $this->key);
        return $object->get();
    }
    
    /**
     * Checks if entry exists
     *
     * @return bool
     */
    public function exists(): bool
    {
        $object = new Value($this->schemas[rand(0, sizeof($this->schemas)-1)], $this->key);
        return $object->exists();
    }
    
    /**
     * Increments existing entry and returns value
     *
     * @param int $step Step of incrementation
     * @throws KeyNotFoundException If entry doesn't exist
     * @return int
     */
    public function increment(int $step = 1): int
    {
        $value = 0;
        foreach($this->schemas as $i=>$schema) {
            if($i==0) {
                $object = new Value($schema, $this->key);
                $value = $object->increment($step);
            } else {
                $object = new Value($schema, $this->key);
                $object->set($value);
            }
        }
        return $value;
    }
    
    /**
     * Decrements existing entry and returns value
     *
     * @param int $step Step of decrementation
     * @throws KeyNotFoundException If entry doesn't exist
     * @return int
     */
    public function decrement(int $step = 1): int
    {
        $value = 0;
        foreach($this->schemas as $i=>$schema) {
            if($i==0) {
                $object = new Value($schema, $this->key);
                $value = $object->decrement($step);
            } else {
                $object = new Value($schema, $this->key);
                $object->set($value);
            }
        }
        return $value;
    }
    
    /**
     * Deletes existing entry
     *
     * @throws KeyNotFoundException If entry doesn't exist
     */
    public function delete(): void
    {
        foreach($this->schemas as $schema) {
            $object = new Value($schema, $this->key);
            $object->delete();
        }
    }
}
