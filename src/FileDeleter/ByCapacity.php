<?php
namespace Lucinda\DB\FileDeleter;

use Lucinda\DB\FileDeleter;

/**
 * Encapsulates a deleter that clears database of all older entries past a max capacity
 */
class ByCapacity implements FileDeleter
{
    private $capacityHeap;
    
    /**
     * Constructs by user-specified maximum database entry capacity
     *
     * @param array $replicas Replicas on whom database is distributed
     * @param int $minCapacity Number of entries allowed to remain if database reached max capacity.
     * @param int $maxCapacity Maximum number of entries allowed to exist in database.
     */
    public function __construct(array $replicas, int $minCapacity, int $maxCapacity)
    {
        $this->capacityHeap = new CapacityHeap($replicas, $minCapacity, $maxCapacity);
    }
    
    /**
     * {@inheritDoc}
     * @see \Lucinda\DB\FileDeleter::delete()
     */
    public function delete(string $folder, string $file): bool
    {
        if (!in_array($file, [".", ".."])) {
            $this->capacityHeap->push($file);
        }
        return false;
    }
    
    /**
     * Gets total of elements deleted
     *
     * @return int Number of elements deleted
     */
    public function getTotal(): int
    {
        return $this->capacityHeap->getTotalDeleted();
    }
}
