<?php

namespace Lucinda\DB\FileDeleter;

use Lucinda\DB\FileDeleter;

/**
 * Encapsulates a deleter that clears database of all entries
 */
class All implements FileDeleter
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
                    unlink($schema."/".$file);
                }
            } else {
                unlink($folder."/".$file);
            }
            return true;
        } else {
            return false;
        }
    }
}
