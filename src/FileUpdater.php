<?php

namespace Lucinda\DB;

/**
 * Defines blueprints of entry value update algorithm
 */
interface FileUpdater
{
    /**
     * Attempts to perform a value update, if applicable
     *
     * @param  mixed $json Existing decoded entry value
     * @return bool Whether update was done.
     */
    public function update(mixed &$json): bool;
}
