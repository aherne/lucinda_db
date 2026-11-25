<?php

namespace Lucinda\DB;

/**
 * Defines a callback invoked for each entry visited by Folder::scan()
 *
 * Implementations may accumulate state or copy entries. Folder supplies .json
 * basenames in filesystem iteration order; implementations used directly are
 * responsible for any additional filename filtering.
 *
 * @see Folder
 */
interface FileInspector
{
    /**
     * Inspects one entry and optionally updates the inspector's accumulated state
     *
     * @param string $folder Schema directory containing the entry
     * @param string $file Entry basename including its .json suffix
     */
    public function inspect(string $folder, string $file): void;
}
