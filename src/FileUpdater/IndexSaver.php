<?php

namespace Lucinda\DB\FileUpdater;

use Lucinda\DB\FileUpdater;

/**
 * Sets one key-to-schema mapping inside a decoded JSON index
 *
 * The caller supplies an array-compatible index to File::update(). The callback
 * changes that array by reference and requests persistence, even when the
 * resulting mapping would be unchanged. It does not move entry files.
 */
final class IndexSaver implements FileUpdater
{
    /**
     * Schema path stored as the index value
     */
    private string $schema;

    /**
     * Index key selected for mutation
     */
    private string $key;

    /**
     * Captures the index mapping selected for mutation
     *
     * @param string $schema Schema path to associate with the key
     * @param string $key Index key to mutate
     */
    public function __construct(string $schema, string $key)
    {
        $this->key = $key;
        $this->schema = $schema;
    }

    /**
     * Mutates the selected mapping and requests persistence
     *
     * Sets the configured key to its schema path, replacing any earlier mapping.
     *
     * @param array<string,string> $json Decoded key-to-schema index, modified in place
     * @return bool Always true, requesting JSON replacement
     */
    public function update(&$json): bool
    {
        $json[$this->key] = $this->schema;
        return true;
    }
}
