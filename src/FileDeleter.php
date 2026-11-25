<?php

namespace Lucinda\DB;

/**
 * Defines the per-entry callback used by Folder::clear()
 *
 * Implementations decide whether to delete an entry and may coordinate matching
 * files in other replicas. The boolean determines Folder's logical deletion count.
 * Deferred algorithms such as ByCapacity instead expose their own totals.
 *
 * @see Folder
 * @see \Lucinda\DB\FileDeleter\ByCapacity
 */
interface FileDeleter
{
    /**
     * Evaluates one scanned entry and performs any applicable deletion
     *
     * @param string $folder Schema directory selected for scanning
     * @param string $file Entry basename including its .json suffix
     * @return bool Whether this callback counts as a deletion for Folder::clear()
     */
    public function delete(string $folder, string $file): bool;
}
