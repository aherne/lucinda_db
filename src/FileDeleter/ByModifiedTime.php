<?php
namespace Lucinda\DB\FileDeleter;

use Lucinda\DB\FileDeleter;

/**
 * Encapsulates a deleter that clears database of all entries older than a last modified time
 */
class ByModifiedTime implements FileDeleter
{
    private $modifiedTime;
    private $replicas = [];
    
    /**
     * Constructs by user-specified minimum last modified time
     *
     * @param int $modifiedTime
     */
    public function __construct(int $modifiedTime, array $replicas)
    {
        $this->modifiedTime = $modifiedTime;
        $this->replicas = $replicas;
    }
    
    /**
     * {@inheritDoc}
     * @see \Lucinda\DB\FileDeleter::delete()
     */
    public function delete(string $folder, string $file): bool
    {
        if (!in_array($file, [".", ".."]) && filemtime($folder."/".$file) < $this->modifiedTime) {
            foreach ($this->replicas as $schema) {
                unlink($schema."/".$file);
            }
            return true;
        } else {
            return false;
        }
    }
}
