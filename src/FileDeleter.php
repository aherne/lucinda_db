<?php
namespace Lucinda\DB;

/**
 * Defines blueprints of entry deletion algorithm
 */
interface FileDeleter
{
    /**
     * Attempts to perform file deletion, if applicable.
     *
     * @param string $folder Schema folder containing file to delete
     * @param string $file File to delete
     * @return bool Whether or not delete was performed
     */
    public function delete(string $folder, string $file): bool;
}
