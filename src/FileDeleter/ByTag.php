<?php
namespace Lucinda\DB\FileDeleter;

use Lucinda\DB\FileDeleter;

/**
 * Encapsulates a deleter that clears database of all entries whose key includes tag
 */
class ByTag implements FileDeleter
{
    private $tag;
    
    /**
     * Constructs by tag to match
     * 
     * @param string $tag Name of tag to match
     */
    public function __construct(string $tag)
    {
        $this->tag = $tag;
    }
    
    /**
     * {@inheritDoc}
     * @see \Lucinda\DB\FileDeleter::delete()
     */
    public function delete(string $folder, string $file): bool
    {
        if (preg_match("/(^|_)".$this->tag."(_|\.json)/", $file)==1) {
            unlink($folder."/".$file);
            return true;
        } else {
            return false;
        }
    }
}