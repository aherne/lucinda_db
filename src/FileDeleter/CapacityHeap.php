<?php

namespace Lucinda\DB\FileDeleter;

/**
 * Encapsulates a queue of files sorted by modification time that shrinks to min capacity when max capacity is reached.
 */
class CapacityHeap extends \SplMaxHeap
{
    protected int $minCapacity;
    protected int $maxCapacity;
    protected int $totalDeleted = 0;
    /**
     * @var string[]
     */
    private array $schemas = [];

    /**
     * Constructs by user-specified maximum/minimum database entry capacity
     *
     * @param string[] $schemas     Replicas on whom database is distributed
     * @param int      $minCapacity Number of entries allowed to remain if database reached max capacity.
     * @param int      $maxCapacity Maximum number of entries allowed to exist in database.
     */
    public function __construct(array $schemas, int $minCapacity, int $maxCapacity)
    {
        $this->schemas = $schemas;
        $this->minCapacity = $minCapacity;
        $this->maxCapacity = $maxCapacity;
    }

    /**
     * {@inheritDoc}
     *
     * @see \SplMaxHeap::compare()
     */
    protected function compare(mixed $value1, mixed $value2): int
    {
        return $value2["date"]-$value1["date"];
    }

    /**
     * Push file to queue
     *
     * @param string $filePath Absolute file location on disk.
     */
    public function push(string $filePath): void
    {
        $randomSchema = $this->schemas[rand(0, sizeof($this->schemas)-1)];
        $this->insert(["file"=>$filePath, "date"=>filemtime($randomSchema."/".$filePath)]);

        // reduce size
        $elementsCount = $this->count();
        if ($elementsCount == $this->maxCapacity) {
            $elementsToDelete = ($elementsCount-$this->minCapacity);
            while ($elementsToDelete > 0) {
                $this->pop();
                $elementsToDelete--;
            }
        }
    }

    /**
     * Pops file from queue and deletes it from disk
     */
    protected function pop(): void
    {
        $info = $this->extract();
        foreach ($this->schemas as $schema) {
            unlink($schema."/".$info["file"]);
        }
        $this->totalDeleted++;
    }

    /**
     * Gets total number of files deleted
     *
     * @return int
     */
    public function getTotalDeleted(): int
    {
        return $this->totalDeleted;
    }
}
