<?php
namespace Lucinda\DB\FileInspector;

use Lucinda\DB\FileInspector;

/**
 * Encapsulates a inspector that selects all entries in schema
 */
class All implements FileInspector
{
    private array $entries = [];
    
    /**
     * {@inheritDoc}
     * @see \Lucinda\DB\FileInspector::inspect()
     */
    public function inspect(string $folder, string $file): void
    {
        if (!in_array($file, [".", ".."])) {
            $this->entries[] = $file;
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
