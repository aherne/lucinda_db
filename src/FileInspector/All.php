<?php

namespace Lucinda\DB\FileInspector;

use Lucinda\DB\FileInspector;

/**
 * Collects every entry basename supplied by a schema scan
 *
 * Results accumulate in visit order for the lifetime of the inspector. Folder
 * filters the .json extension; direct calls are only checked against dot entries.
 *
 * @see \Lucinda\DB\Folder
 */
final class All implements FileInspector
{
    /**
     * @var string[] Filenames accumulated across inspections
     */
    private array $entries = [];

    /**
     * Processes one filename supplied by the folder scan
     *
     * Appends the basename unless it is a dot-directory entry.
     *
     * @param string $folder Source schema directory
     * @param string $file Entry basename including its .json suffix
     */
    public function inspect(string $folder, string $file): void
    {
        if (!in_array($file, [".", ".."])) {
            $this->entries[] = $file;
        }
    }

    /**
     * Gets the filenames collected so far without clearing the inspector
     *
     * @return string[] Matching basenames in scan order
     */
    public function getEntries(): array
    {
        return $this->entries;
    }
}
