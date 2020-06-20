<?php
namespace Lucinda\DB\FileDeleter;

use Lucinda\DB\FileDeleter;

/**
 * Encapsulates a deleter that clears database of all older entries past a max capacity
 */
class ByCapacity implements FileDeleter
{
    private $minCapacity;
    private $maxCapacity;
    private $elements = [];
    private $deleteCount = 0;
    
    /**
     * Constructs by user-specified maximum database entry capacity
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
     * @see \Lucinda\DB\FileDeleter::delete()
     */
    public function delete(string $folder, string $file): bool
    {
        if (strpos($file, ".json")!==false) {
            $this->elements[$folder."/".$file] = filemtime($folder."/".$file);
            if (sizeof($this->elements) == $this->maxCapacity) {
                asort($this->elements);
                
                // pop first COUNT - MIN_CAPACITY elements
                $i = 0;
                $elementsToDelete = (sizeof($this->elements)-$this->minCapacity);
                foreach ($this->elements as $path=>$lastModifiedTime) {
                    if ($i < $elementsToDelete) {
                        unlink($path);
                        unset($this->items[$path]);
                        $this->deleteCount++;
                    } else {
                        break;
                    }
                    $i++;
                }
                return true;
            }
        }
        return false;
    }
    
    /**
     * Gets total of deleted elements
     *
     * @return int Number of elements deleted
     */
    public function getTotal(): int
    {
        return $this->deleteCount;
    }
}
