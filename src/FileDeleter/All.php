<?php

namespace Lucinda\DB\FileDeleter;

use Lucinda\DB\FileDeleter;

/**
 * Deletes each visited entry locally or from all configured replicas
 *
 * When replicas are supplied, deletion targets those paths rather than the scanned
 * folder. Entry files and legacy sibling .lock files are removed directly, without
 * File mutexes. The callback counts one logical entry rather than replica files.
 */
final class All implements FileDeleter
{
    /**
     * @var string[] Replica directories targeted by a matching deletion
     */
    private array $replicas = [];

    /**
     * Captures the optional replica deletion scope
     *
     * @param string[] $replicas Replica paths, or an empty list to use the scanned folder
     */
    public function __construct(array $replicas = [])
    {
        $this->replicas = $replicas;
    }

    /**
     * Evaluates the entry and removes qualifying replica files
     *
     * Removes matching paths directly; no entry mutex is acquired.
     *
     * @param string $folder Directory containing the scanned filename
     * @param string $file Entry basename including its .json suffix
     * @return bool True when the entry qualifies for this deletion algorithm
     */
    public function delete(string $folder, string $file): bool
    {
        if (!in_array($file, [".", ".."])) {
            if ($this->replicas) {
                foreach ($this->replicas as $schema) {
                    $this->unlinkEntry($schema."/".$file);
                }
            } else {
                $this->unlinkEntry($folder."/".$file);
            }
            return true;
        } else {
            return false;
        }
    }

    /**
     * Removes an entry file and any legacy sibling lock file
     *
     * Missing paths are skipped. The modern temporary-directory mutex is unaffected.
     *
     * @param string $path Complete entry filename to remove
     */
    private function unlinkEntry(string $path): void
    {
        if (file_exists($path)) {
            unlink($path);
        }
        if (file_exists($path.".lock")) {
            unlink($path.".lock");
        }
    }
}
