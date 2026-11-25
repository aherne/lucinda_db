<?php

namespace Lucinda\DB;

/**
 * Defines an in-memory transformation used inside File's mutation mutex
 *
 * The updater receives a decoded JSON value by reference. Returning true asks File
 * to encode and persist the changed value; false leaves the stored file untouched.
 * A missing or empty file is supplied as an empty array.
 *
 * @see File
 */
interface FileUpdater
{
    /**
     * Attempts to transform the referenced decoded JSON value
     *
     * @param mixed $json Current value, modified in place when an update is needed
     * @return bool True to persist the resulting value; false to skip replacement
     */
    public function update(mixed &$json): bool;
}
