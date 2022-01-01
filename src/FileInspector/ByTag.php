<?php
namespace Lucinda\DB\FileInspector;

use Lucinda\DB\FileInspector;

/**
 * Encapsulates a inspector that selects all entries in schema by tag
 */
class ByTag implements FileInspector
{
    private string $tag;
    private array $entries = [];
    
    public function __construct(string $tag)
    {
        $this->tag = $tag;
    }
    
    /**
     * {@inheritDoc}
     * @see \Lucinda\DB\FileInspector::inspect()
     */
    public function inspect(string $folder, string $file): void
    {
        if (preg_match("/(^|_)".$this->tag."(_|\.json)/", $file)==1) {
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
