<?php

namespace Lucinda\DB;

/**
 * Coordinates one tagged entry across an ordered set of replica folders
 *
 * set() writes every replica; exists() accepts any present entry. get() randomizes
 * read order, accepts the first decodable value, and attempts to repair missing,
 * malformed, or differing copies. It does not elect a newest or majority value.
 *
 * Counter operations update the first configured replica, then distribute the
 * result. Each mutation uses File locking independently; the complete replica
 * operation is not atomic. Schemas must be nonempty and indexed from zero.
 *
 * @see Value
 * @see Key
 * @see File
 */
final class ValueDriver implements ValueOperations
{
    /**
     * @var string[] Replica paths in configured order
     */
    private array $schemas = [];

    /**
     * Canonical key shared by every replica
     */
    private string $key;

    /**
     * Builds a canonical key and captures the ordered replica paths
     *
     * @param string[] $schemas Nonempty replica list indexed consecutively from zero
     * @param string[] $tags Nonempty tags used to generate the entry key
     * @throws KeyException If tag validation fails
     */
    public function __construct(array $schemas, array $tags)
    {
        $this->schemas = $schemas;

        $object = new Key($tags);
        $this->key = $object->getValue();
    }

    /**
     * Writes the same value to each replica in configured order
     *
     * Each file write is separately locked. A failure can leave a partially updated
     * replica set; the writes are not one transaction.
     *
     * @param mixed $value JSON-encodable value to replicate
     * @throws \JsonException If encoding fails
     * @throws LockException If a replica mutex cannot be acquired
     * @throws \RuntimeException If a replica file cannot be replaced
     */
    public function set(mixed $value): void
    {
        foreach ($this->schemas as $schema) {
            $object = new Value($schema, $this->key);
            $object->set($value);
        }
    }

    /**
     * Reads the first decodable replica in randomized order and attempts repairs
     *
     * Missing entries and malformed JSON are skipped. A successful value is returned
     * even if repairing another replica fails. If every attempt fails, the last
     * missing/JSON failure determines the exception reported.
     *
     * @return mixed Value from the first successful read
     * @throws KeyNotFoundException If no successful read exists and the last failure is a missing key
     * @throws \JsonException If no successful read exists and the last failure is malformed JSON
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
     * Checks whether at least one replica contains the entry
     *
     * Presence alone does not validate the stored JSON.
     *
     * @return bool True if an entry path exists in any configured schema
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
     * Increments the first replica and copies the resulting value to the others
     *
     * Only the first replica's read-modify-write is one locked operation. Subsequent
     * replica writes are separate and may fail after the first value has changed.
     *
     * @param int $step Amount to add, defaulting to one
     * @return int Result computed in the first configured replica
     * @throws KeyNotFoundException If the first replica has no entry
     * @throws \JsonException If JSON conversion fails
     * @throws LockException If a mutation mutex cannot be acquired
     * @throws \RuntimeException If reading the first replica or writing a replica fails
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
     * Decrements the first replica and copies the resulting value to the others
     *
     * Only the first replica's read-modify-write is one locked operation. Subsequent
     * replica writes are separate and may fail after the first value has changed.
     *
     * @param int $step Amount to subtract, defaulting to one
     * @return int Result computed in the first configured replica
     * @throws KeyNotFoundException If the first replica has no entry
     * @throws \JsonException If JSON conversion fails
     * @throws LockException If a mutation mutex cannot be acquired
     * @throws \RuntimeException If reading the first replica or writing a replica fails
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
     * Deletes the entry wherever it exists in the configured replica set
     *
     * Missing individual replicas are skipped. Deletions use separate file mutexes.
     *
     * @throws KeyNotFoundException If the entry exists in no replica
     * @throws LockException If a replica mutex cannot be acquired
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
     * Gets a randomized copy of the configured schema list
     *
     * @return string[] Replica paths in an independently shuffled order
     */
    private function getShuffledSchemas(): array
    {
        $schemas = $this->schemas;
        shuffle($schemas);
        return $schemas;
    }

    /**
     * Attempts to replace absent, malformed, or differing replicas after a read
     *
     * All repair failures are suppressed so a healthy read can still succeed. The
     * first successful read supplies the value; there is no version or quorum check.
     *
     * @param mixed $value Successfully decoded value to use for repairs
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
