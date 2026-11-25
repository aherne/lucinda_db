<?php

namespace Lucinda\DB\FileInspector;

use Lucinda\DB\FileInspector;

/**
 * Counts entry callbacks without reading file contents
 *
 * The running total is retained across scans. Folder supplies only entry
 * filenames; direct calls are checked only against the two dot-directory names.
 *
 * @see \Lucinda\DB\Folder
 */
final class Counter implements FileInspector
{
    /**
     * Running number of accepted entry callbacks
     */
    private int $total = 0;

    /**
     * Processes one filename supplied by the folder scan
     *
     * Increments the total unless the name is a dot-directory entry.
     *
     * @param string $folder Source schema directory
     * @param string $file Entry basename including its .json suffix
     */
    public function inspect(string $folder, string $file): void
    {
        if (!in_array($file, [".", ".."])) {
            ++$this->total;
        }
    }

    /**
     * Gets the number of entries visited so far
     *
     * @return int Accumulated count since construction
     */
    public function getValue(): int
    {
        return $this->total;
    }
}
