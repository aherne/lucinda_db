<?php

namespace Lucinda\DB\FileUpdater;

use Lucinda\DB\FileUpdater;

/**
 * Encapsulates an updater that decrements existing entry value (assuming by default it's integer)
 */
class Decrement implements FileUpdater
{
    private int $step;
    private int $value;

    /**
     * Constructs by entry key
     *
     * @param int $step Step of decrementation
     */
    public function __construct(int $step = 1)
    {
        $this->step = $step;
    }

    /**
     * {@inheritDoc}
     *
     * @see \Lucinda\DB\FileUpdater::update()
     */
    public function update(mixed &$json): bool
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
