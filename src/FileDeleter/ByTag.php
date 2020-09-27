<?php
namespace Lucinda\DB\FileDeleter;

use Lucinda\DB\FileDeleter;

/**
 * Encapsulates a deleter that clears database of all entries whose key includes tag
 */
class ByTag implements FileDeleter
{
    private $tag;
    private $replicas = [];
    
    /**
     * Constructs by tag to match
     *
     * @param string $tag Name of tag to match
     * @param array $replicas Replicas on whom database is distributed
     */
    public function __construct(string $tag, array $replicas = [])
    {
        $this->tag = $tag;
        $this->replicas = $replicas;
    }
    
    /**
     * {@inheritDoc}
     * @see \Lucinda\DB\FileDeleter::delete()
     */
    public function delete(string $folder, string $file): bool
    {
        if (!in_array($file, [".", ".."]) && preg_match("/(^|_)".$this->tag."(_|\.json)/", $file)==1) {
            if ($this->replicas) {
                foreach ($this->replicas as $schema) {
                    unlink($schema."/".$file);
                }
            } else {
                unlink($folder."/".$file);
            }
            return true;
        } else {
            return false;
        }
    }
}
