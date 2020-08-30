<?php
namespace Lucinda\DB\FileInspector;

use Lucinda\DB\FileInspector;

/**
 * Encapsulates a inspector that just iterates and counts database entries found in schema
 */
class Counter implements FileInspector
{
    private $total = 0;
    
    /**
     * {@inheritDoc}
     * @see \Lucinda\DB\FileInspector::inspect()
     */
    public function inspect(string $folder, string $file): void
    {
        if (strpos($file, ".json")!==false) {
            ++$this->total;
        }
    }
    
    /**
     * Gets total number of entries found in schema
     *
     * @return int
     */
    public function getValue(): int
    {
        return $this->total;
    }
}
