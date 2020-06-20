<?php
namespace Lucinda\DB\FileUpdater;

use Lucinda\DB\FileUpdater;

/**
 * Encapsulates an updater that increments existing entry value (assuming by default it's integer)
 */
class Increment implements FileUpdater
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
        $json++;
        $this->value = $json;
        return true;
    }
    
    /**
     * Gets incremented value
     *
     * @return int
     */
    public function getValue(): int
    {
        return $this->value;
    }
}