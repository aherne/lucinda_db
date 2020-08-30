<?php
namespace Lucinda\DB\FileDeleter;

/**
 * Encapsulates a queue of files sorted by modification time that shrinks to min capacity when max capacity is reached.
 */
class CapacityHeap extends \SplMaxHeap
{
    private $minCapacity;
    private $maxCapacity;
    
    private $totalDeleted = 0;
    
    /**
     * Constructs by user-specified maximum/minimum database entry capacity
     *
     * @param int $minCapacity Number of entries allowed to remain if database reached max capacity.
     * @param int $maxCapacity Maximum number of entries allowed to exist in database.
     */
    public function __construct(int $minCapacity, int $maxCapacity)
    {
        $this->minCapacity = $minCapacity;
        $this->maxCapacity = $maxCapacity;
    }
    
    /**
     * {@inheritDoc}
     * @see \SplMaxHeap::compare()
     */
    protected function compare($value1, $value2)
    {
        return $value2["date"]-$value1["date"];
    }
    
    /**
     * Push file to queue
     *
     * @param string $filePath Absolute file location on disk.
     */
    public function push($filePath)
    {
        $this->insert(["file"=>$filePath, "date"=>filemtime($filePath)]);
        
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
    private function pop()
    {
        $info = $this->extract();
        unlink($info["file"]);
        $this->totalDeleted++;
    }
    
    /**
     * Gets total number of files deleted
     *
     * @return number
     */
    public function getTotalDeleted()
    {
        return $this->totalDeleted;
    }
}
