<?php
namespace Lucinda\DB\FileDeleter;

use Lucinda\DB\FileDeleter;

/**
 * Encapsulates a deleter that clears database of all entries older than a last modified time
 */
class ByModifiedTime implements FileDeleter
{
    private $modifiedTime;
    
    /**
     * Constructs by user-specified minimum last modified time
     * 
     * @param int $modifiedTime
     */
    public function __construct(int $modifiedTime)
    {
        $this->modifiedTime = $modifiedTime;
    }
    
    /**
     * {@inheritDoc}
     * @see \Lucinda\DB\FileDeleter::delete()
     */
    public function delete(string $folder, string $file): bool
    {
        if (strpos($file, ".json")!==false && filemtime($folder."/".$file) < $this->modifiedTime) {
            unlink($folder."/".$file);
            return true;
        } else {
            return false;
        }
    }
}
