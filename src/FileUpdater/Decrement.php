<?php
namespace Lucinda\DB\FileUpdater;

use Lucinda\DB\FileUpdater;

/**
 * Encapsulates an updater that decrements existing entry value (assuming by default it's integer)
 */
class Decrement implements FileUpdater
{
    private $key;
    private $step;
    private $value;
    
    /**
     * Constructs by entry key
     *
     * @param string $key Key of entry in database
     * @param int $step Step of decrementation
     */
    public function __construct(string $key, int $step = 1)
    {
        $this->key = $key;
        $this->step = $step;
    }
    
    /**
     * {@inheritDoc}
     * @see \Lucinda\DB\FileUpdater::update()
     */
    public function update(&$json): bool
    {
        $json -= $this->step;
        $this->value = $json;
        return true;
    }
    
    /**
     * Gets decremented value
     *
     * @return int
     */
    public function getValue(): int
    {
        return $this->value;
    }
}
