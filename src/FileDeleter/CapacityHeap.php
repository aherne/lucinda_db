<?php

namespace Lucinda\DB\FileDeleter;

/**
 * Retains recent scanned entries and evicts older entries at a size threshold
 *
 * Candidates store an entry basename and a modification timestamp read from one
 * random replica. Reversed timestamp comparison makes the oldest candidate the
 * next extracted element. Each time the heap reaches maxCapacity, entries are
 * removed until minCapacity remains; their filenames are deleted across schemas.
 *
 * Deletion counts logical entries rather than physical files. Equal timestamps
 * have no stable eviction order. Filesystem deletion bypasses File mutexes.
 *
 * @see ByCapacity
 */
final class CapacityHeap extends \SplMaxHeap
{
    /**
     * Heap population retained after each eviction batch
     */
    private int $minCapacity;

    /**
     * Heap size triggering an eviction batch
     */
    private int $maxCapacity;

    /**
     * Cumulative number of logical candidates evicted
     */
    private int $totalDeleted = 0;

    /**
     * @var string[] Replica directories targeted by eviction
     */
    private array $schemas = [];

    /**
     * Captures replica paths and the heap eviction thresholds
     *
     * @param string[] $schemas Nonempty replica list indexed consecutively from zero
     * @param int $minCapacity Entries retained after a threshold-triggered batch
     * @param int $maxCapacity Trigger size; use 0 <= minCapacity < maxCapacity
     */
    public function __construct(array $schemas, int $minCapacity, int $maxCapacity)
    {
        $this->schemas = $schemas;
        $this->minCapacity = $minCapacity;
        $this->maxCapacity = $maxCapacity;
    }

    /**
     * Orders candidates so extraction chooses the oldest timestamp
     *
     * @param array<string,int|string> $value1 Candidate containing file and date
     * @param array<string,int|string> $value2 Candidate containing file and date
     * @return int Positive when the first candidate is older than the second
     */
    protected function compare(mixed $value1, mixed $value2): int
    {
        return $value2["date"]-$value1["date"];
    }

    /**
     * Queues an entry basename and evicts oldest candidates at the threshold
     *
     * The argument is a basename relative to every schema, despite its parameter name.
     *
     * @param string $filePath Entry basename, for example users_roles.json
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
     * Extracts the oldest candidate and removes its files from every schema
     *
     * Also removes legacy sibling lock files and increments the logical eviction count.
     */
    protected function pop(): void
    {
        $info = $this->extract();
        foreach ($this->schemas as $schema) {
            $path = $schema."/".$info["file"];
            if (file_exists($path)) {
                unlink($path);
            }
            if (file_exists($path.".lock")) {
                unlink($path.".lock");
            }
        }
        $this->totalDeleted++;
    }

    /**
     * Gets the cumulative number of candidates evicted
     *
     * @return int Logical entries removed since construction
     */
    public function getTotalDeleted(): int
    {
        return $this->totalDeleted;
    }
}
