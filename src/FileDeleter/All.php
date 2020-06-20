<?php
namespace Lucinda\DB\FileDeleter;

use Lucinda\DB\FileDeleter;

/**
 * Encapsulates a deleter that clears database of all entries
 */
class All implements FileDeleter
{
    /**
     * {@inheritDoc}
     * @see \Lucinda\DB\FileDeleter::delete()
     */
    public function delete(string $folder, string $file): bool
    {
        if (strpos($file, ".json")!==false) {
            unlink($folder."/".$file);
            return true;
        } else {
            return false;
        }
    }
}