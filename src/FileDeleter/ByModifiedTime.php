<?php

namespace Lucinda\DB\FileDeleter;

use Lucinda\DB\FileDeleter;

/**
 * Deletes entries older than an exclusive modification-time cutoff
 *
 * The scanned file's timestamp controls deletion of that filename from every
 * configured replica. Files exactly at the cutoff are retained. The replica list
 * must name the intended deletion scope; an empty list deletes no physical files.
 */
final class ByModifiedTime implements FileDeleter
{
    /**
     * Exclusive Unix timestamp cutoff for entry removal
     */
    private int $modifiedTime;

    /**
     * @var string[] Replica directories targeted by a matching deletion
     */
    private array $replicas = [];

    /**
     * Captures the cutoff timestamp and replica deletion scope
     *
     * @param int $modifiedTime Exclusive Unix timestamp; older entries qualify
     * @param string[] $replicas Replica directories from which qualifying filenames are deleted
     */
    public function __construct(int $modifiedTime, array $replicas)
    {
        $this->modifiedTime = $modifiedTime;
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
        if (!in_array($file, [".", ".."]) && filemtime($folder."/".$file) < $this->modifiedTime) {
            foreach ($this->replicas as $schema) {
                $this->unlinkEntry($schema."/".$file);
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
