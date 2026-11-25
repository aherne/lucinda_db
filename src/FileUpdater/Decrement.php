<?php

namespace Lucinda\DB\FileUpdater;

use Lucinda\DB\FileUpdater;

/**
 * Transforms a decoded integer-compatible value by arithmetic decrement
 *
 * File invokes this callback while holding its entry mutex. Each update stores
 * the resulting integer for getValue() and requests persistence. Call update()
 * before reading the result; arbitrary JSON payload types are not validated.
 */
final class Decrement implements FileUpdater
{
    /**
     * Amount to subtract during each update
     */
    private int $step;

    /**
     * Result of the most recent update; initialized by update()
     */
    private int $value;

    /**
     * Captures the counter step used by subsequent updates
     *
     * @param int $step Amount to subtract, defaulting to one
     */
    public function __construct(int $step = 1)
    {
        $this->step = $step;
    }

    /**
     * Applies the configured step and captures the decremented value
     *
     * @param mixed $json Integer-compatible value modified in place
     * @return bool Always true, requesting that File persist the result
     */
    public function update(mixed &$json): bool
    {
        $json -= $this->step;
        $this->value = $json;
        return true;
    }

    /**
     * Gets the counter result from the most recent update
     *
     * @return int Result captured by update(); update() must have run first
     */
    public function getValue(): int
    {
        return $this->value;
    }
}
