<?php

namespace Lucinda\DB;

/**
 * Wrapper of Value for data distribution, allowing traffic leveraging on multiple disks
 */
class ValueDriver implements ValueOperations
{
    /**
     * @var string[]
     */
    private array $schemas = [];
    private string $key;

    /**
     * Sets up database entry to query based on Configuration object and tags key is composed of
     *
     * @param  string[] $schemas List of schemas value is stored into
     * @param  string[] $tags    List of tags key is composed of
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
     * @param  mixed $value
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
        $lastException = null;
        foreach ($this->getShuffledSchemas() as $schema) {
            $object = new Value($schema, $this->key);
            try {
                $value = $object->get();
                $this->repairReplicas($value);
                return $value;
            } catch (KeyNotFoundException | \JsonException $exception) {
                $lastException = $exception;
            }
        }

        if ($lastException instanceof \JsonException) {
            throw $lastException;
        }

        throw new KeyNotFoundException($this->key);
    }

    /**
     * Checks if entry exists
     *
     * @return bool
     */
    public function exists(): bool
    {
        foreach ($this->schemas as $schema) {
            $object = new Value($schema, $this->key);
            if ($object->exists()) {
                return true;
            }
        }
        return false;
    }

    /**
     * Increments existing entry and returns value
     *
     * @param  int $step Step of incrementation
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
     * @param  int $step Step of decrementation
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
        $found = false;
        foreach ($this->schemas as $schema) {
            $object = new Value($schema, $this->key);
            if ($object->exists()) {
                $found = true;
                $object->delete();
            }
        }

        if (!$found) {
            throw new KeyNotFoundException($this->key);
        }
    }

    /**
     * Gets schemas in randomized order.
     *
     * @return string[]
     */
    private function getShuffledSchemas(): array
    {
        $schemas = $this->schemas;
        shuffle($schemas);
        return $schemas;
    }

    /**
     * Repairs missing or corrupted replicas after a successful read.
     *
     * @param mixed $value
     */
    private function repairReplicas(mixed $value): void
    {
        foreach ($this->schemas as $schema) {
            $object = new Value($schema, $this->key);
            try {
                if (!$object->exists() || $object->get() !== $value) {
                    $object->set($value);
                }
            } catch (KeyNotFoundException | \JsonException) {
                try {
                    $object->set($value);
                } catch (\Throwable) {
                    // A read from one healthy replica should not fail because another replica cannot be repaired.
                }
            } catch (\Throwable) {
                // A read from one healthy replica should not fail because another replica cannot be repaired.
            }
        }
    }
}
