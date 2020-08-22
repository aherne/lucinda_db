<?php
namespace Lucinda\DB\FileUpdater;

use Lucinda\DB\FileUpdater;

/**
 * Encapsulates a setter of an index entry
 */
class IndexSaver implements FileUpdater
{
    private $key;
    private $value;
    
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
        $json[$this->key] = $this->key;
        return true;
    }
}