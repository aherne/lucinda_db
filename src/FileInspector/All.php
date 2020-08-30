<?php
namespace Lucinda\DB\FileInspector;

use Lucinda\DB\FileInspector;

/**
 * Encapsulates a inspector that selects all entries in schema
 */
class All implements FileInspector
{
    private $entries = [];
    
    /**
     * {@inheritDoc}
     * @see \Lucinda\DB\FileInspector::inspect()
     */
    public function inspect(string $folder, string $file): void
    {
        if (strpos($file, ".json")!==false) {
            $this->entries[] = substr($file, 0, -5);
        }
    }
    
    /**
     * Gets all entries found in schema
     *
     * @return array
     */
    public function getEntries(): array
    {
        return $this->entries;
    }
}