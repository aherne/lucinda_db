<?php
namespace Lucinda\DB;

/**
 * Wrapper of Value for data distribution, allowing traffic leveraging on multiple disks
 */
class ValueDriver implements ValueOperations
{
    private array $schemas = [];
    private string $key;

    /**
     * Sets up database entry to query based on Configuration object and tags key is composed of
     *
     * @param array $schemas List of schemas value is stored into
     * @param array $tags List of tags key is composed of
     * @throws KeyException
     */
    public function __construct(array $schemas, array $tags)
    {
        $this->schemas = $schemas;
        
        $object = new Key($tags);
        $this->key = $object->getValue();
    }

    /**
     * Sets entry value
     *
     * @param mixed $value
     * @throws \JsonException
     */
    public function set(mixed $value): void
    {
        foreach ($this->schemas as $schema) {
            $object = new Value($schema, $this->key);
            $object->set($value);
        }
    }

    /**
     * Gets existing entry value
     *
     * @throws KeyNotFoundException If entry doesn't exist
     * @throws \JsonException
     * @return mixed
     */
    public function get(): mixed
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
     * @return int
     * @throws \JsonException
     * @throws KeyNotFoundException If entry doesn't exist
     * @throws LockException
     */
    public function increment(int $step = 1): int
    {
        $value = 0;
        foreach ($this->schemas as $i=>$schema) {
            $object = new Value($schema, $this->key);
            if ($i==0) {
                $value = $object->increment($step);
            } else {
                $object->set($value);
            }
        }
        return $value;
    }

    /**
     * Decrements existing entry and returns value
     *
     * @param int $step Step of decrementation
     * @return int
     * @throws \JsonException
     * @throws KeyNotFoundException If entry doesn't exist
     * @throws LockException
     */
    public function decrement(int $step = 1): int
    {
        $value = 0;
        foreach ($this->schemas as $i=>$schema) {
            $object = new Value($schema, $this->key);
            if ($i==0) {
                $value = $object->decrement($step);
            } else {
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
        foreach ($this->schemas as $schema) {
            $object = new Value($schema, $this->key);
            $object->delete();
        }
    }
}
