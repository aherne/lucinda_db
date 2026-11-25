<?php

namespace Lucinda\DB\FileInspector;

use Lucinda\DB\FileInspector;

/**
 * Copies missing entry files into a destination schema
 *
 * Existing destination filenames are skipped without comparing contents. Copied
 * files retain their source modification times. Copying bypasses File mutexes
 * and atomic JSON replacement, so callers coordinate membership changes.
 *
 * @see \Lucinda\DB\Folder
 */
final class Importer implements FileInspector
{
    /**
     * Directory receiving missing source entries
     */
    private string $destinationSchema;

    /**
     * Captures the destination directory for subsequent imports
     *
     * @param string $destinationSchema Existing writable destination schema
     */
    public function __construct(string $destinationSchema)
    {
        $this->destinationSchema = $destinationSchema;
    }

    /**
     * Processes one filename supplied by the folder scan
     *
     * Copies the source and its timestamp only if the destination is absent.
     *
     * @param string $folder Source schema directory
     * @param string $file Entry basename including its .json suffix
     */
    public function inspect(string $folder, string $file): void
    {
        if (!in_array($file, [".", ".."]) && !file_exists($this->destinationSchema."/".$file)) {
            copy($folder."/".$file, $this->destinationSchema."/".$file);
            touch($this->destinationSchema."/".$file, filemtime($folder."/".$file));
        }
    }
}
