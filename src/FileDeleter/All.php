<?php

namespace Lucinda\DB\FileDeleter;

use Lucinda\DB\FileDeleter;

/**
 * Encapsulates a deleter that clears database of all entries
 */
final class All implements FileDeleter
{
    /**
     * @var string[]
     */
    private array $replicas = [];

    /**
     * Constructs an all deleter
     *
     * @param string[] $replicas Replicas on whom database is distributed
     */
    public function __construct(array $replicas = [])
    {
        $this->replicas = $replicas;
    }

    /**
     * {@inheritDoc}
     *
     * @see \Lucinda\DB\FileDeleter::delete()
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
