<?php

namespace Lucinda\DB\FileDeleter;

use Lucinda\DB\FileDeleter;

/**
 * Encapsulates a deleter that clears database of all entries older than a last modified time
 */
class ByModifiedTime implements FileDeleter
{
    private int $modifiedTime;
    /**
     * @var string[]
     */
    private array $replicas = [];

    /**
     * Constructs by user-specified minimum last modified time
     *
     * @param int      $modifiedTime
     * @param string[] $replicas
     */
    public function __construct(int $modifiedTime, array $replicas)
    {
        $this->modifiedTime = $modifiedTime;
        $this->replicas = $replicas;
    }

    /**
     * {@inheritDoc}
     *
     * @see \Lucinda\DB\FileDeleter::delete()
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
     * Deletes entry and sibling lock if found.
     *
     * @param string $path
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
