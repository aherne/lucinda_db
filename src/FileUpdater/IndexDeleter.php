<?php
namespace Lucinda\DB\FileUpdater;

use Lucinda\DB\FileUpdater;

/**
 * Encapsulates a deleter of an index entry
 */
class IndexDeleter implements FileUpdater
{
    private string $key;
    
    /**
     * Constructs by entry key
     *
     * @param string $key Key of entry in database
     */
    public function __construct(string $key)
    {
        $this->key = $key;
    }
    
    /**
     * {@inheritDoc}
     * @see \Lucinda\DB\FileUpdater::update()
     */
    public function update(&$json): bool
    {
        unset($json[$this->key]);
        return true;
    }
}
