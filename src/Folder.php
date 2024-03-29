<?php

namespace Lucinda\DB;

/**
 * Encapsulates a folder and its operations
 */
class Folder
{
    private string $path;

    /**
     * Constructs folder by absolute path
     *
     * @param string $path
     */
    public function __construct(string $path)
    {
        $this->path =$path;
    }

    /**
     * Creates folder
     *
     * @param  int $permissions Rights folder is
     * @return bool Whether or not folder was created
     */
    public function create(int $permissions): bool
    {
        return mkdir($this->path, $permissions);
    }

    /**
     * Checks if folder exists
     *
     * @return bool
     */
    public function exists(): bool
    {
        return is_dir($this->path);
    }

    /**
     * Checks if folder is writable
     *
     * @return bool
     */
    public function isWritable(): bool
    {
        return is_writable($this->path);
    }

    /**
     * Scans folder of files and applies inspection algorithm on each entry
     *
     * @param FileInspector $inspector
     */
    public function scan(FileInspector $inspector): void
    {
        $handle = opendir($this->path);
        while (($file = readdir($handle)) !== false) {
            $inspector->inspect($this->path, $file);
        }
        closedir($handle);
    }

    /**
     * Clears folder of files matching callback and returns how many were deleted
     *
     * @param  FileDeleter $deleter Encapsulates algorithm of file deletion
     * @return int Number of files deleted
     */
    public function clear(FileDeleter $deleter): int
    {
        $result = 0;
        $handle = opendir($this->path);
        while (($file = readdir($handle)) !== false) {
            if ($deleter->delete($this->path, $file)) {
                $result++;
            }
        }
        closedir($handle);
        return $result;
    }

    /**
     * Deletes folder from disk
     */
    public function delete(): void
    {
        rmdir($this->path);
    }
}
