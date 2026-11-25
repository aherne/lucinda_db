<?php

namespace Lucinda\DB\FileUpdater;

use Lucinda\DB\FileUpdater;

/**
 * Removes one key-to-schema mapping from a decoded JSON index
 *
 * The caller supplies an array-compatible index to File::update(). The callback
 * changes that array by reference and requests persistence, even when the
 * resulting mapping would be unchanged. It does not move entry files.
 */
final class IndexDeleter implements FileUpdater
{
    /**
     * Index key selected for mutation
     */
    private string $key;

    /**
     * Captures the index mapping selected for mutation
     *
     * @param string $key Index key to mutate
     */
    public function __construct(string $key)
    {
        $this->key = $key;
    }

    /**
     * Mutates the selected mapping and requests persistence
     *
     * Unsets the configured key; an absent mapping is harmless.
     *
     * @param array<string,string> $json Decoded key-to-schema index, modified in place
     * @return bool Always true, requesting JSON replacement
     */
    public function update(&$json): bool
    {
        unset($json[$this->key]);
        return true;
    }
}
