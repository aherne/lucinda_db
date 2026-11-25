<?php

namespace Lucinda\DB\FileDeleter;

use Lucinda\DB\FileDeleter;

/**
 * Feeds scanned entry filenames into a timestamp-based eviction heap
 *
 * Each callback queues an entry and may trigger eviction across replicas. delete()
 * always returns false, so Folder::clear() cannot provide this algorithm's count;
 * call getTotal() after the scan. The same instance retains its heap and total.
 *
 * @see CapacityHeap
 */
final class ByCapacity implements FileDeleter
{
    /**
     * Heap retaining candidates and counting evictions
     */
    private CapacityHeap $capacityHeap;

    /**
     * Creates the heap using replica paths and eviction thresholds
     *
     * @param string[] $replicas Nonempty replica list indexed consecutively from zero
     * @param int $minCapacity Entries retained after each eviction batch
     * @param int $maxCapacity Trigger size; use 0 <= minCapacity < maxCapacity
     */
    public function __construct(array $replicas, int $minCapacity, int $maxCapacity)
    {
        $this->capacityHeap = new CapacityHeap($replicas, $minCapacity, $maxCapacity);
    }

    /**
     * Queues a filename and allows the heap to evict entries as needed
     *
     * @param string $folder Scanned schema directory; timestamp selection is handled by the heap
     * @param string $file Entry basename including its .json suffix
     * @return bool Always false; use getTotal() for the actual deletion count
     */
    public function delete(string $folder, string $file): bool
    {
        if (!in_array($file, [".", ".."])) {
            $this->capacityHeap->push($file);
        }
        return false;
    }

    /**
     * Gets the cumulative logical-entry eviction count
     *
     * @return int Entries removed by this instance's heap
     */
    public function getTotal(): int
    {
        return $this->capacityHeap->getTotalDeleted();
    }
}
