<?php
namespace Lucinda\DB\FileUpdater;

use Lucinda\DB\FileUpdater;

/**
 * Encapsulates a setter of an index entry
 */
class IndexSaver implements FileUpdater
{
    private string $schema;
    private string $key;
    
    /**
     * Constructs by entry key
     *
     * @param string $schema Folder where entry is saved
     * @param string $key Key of entry in database
     */
    public function __construct(string $schema, string $key)
    {
        $this->key = $key;
        $this->schema = $schema;
    }
    
    /**
     * {@inheritDoc}
     * @see \Lucinda\DB\FileUpdater::update()
     */
    public function update(&$json): bool
    {
        $json[$this->key] = $this->schema;
        return true;
    }
}
