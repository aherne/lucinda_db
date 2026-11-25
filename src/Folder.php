<?php

namespace Lucinda\DB;

/**
 * Traverses and mutates one filesystem directory for entry algorithms
 *
 * scan() and clear() dispatch only filenames ending in .json, without sorting or
 * reading their contents. Callbacks own inspection and deletion behavior. The
 * extension filter checks names, not file type. Direct folder deletion removes
 * immediate regular files of every extension, then attempts rmdir().
 *
 * Directory operations use PHP filesystem functions and may emit warnings when
 * paths or permissions are invalid. They do not take entry or directory mutexes.
 *
 * @see FileInspector
 * @see FileDeleter
 */
final class Folder
{
    /**
     * Directory path retained for filesystem operations
     */
    private string $path;

    /**
     * Captures the folder path without creating or validating it
     *
     * @param string $path Directory on which subsequent operations act
     */
    public function __construct(string $path)
    {
        $this->path =$path;
    }

    /**
     * Creates one directory using the supplied permissions
     *
     * The process umask may reduce the requested permissions; parents are not created.
     *
     * @param int $permissions Requested octal permission mode, such as 0777
     * @return bool Whether mkdir() successfully created the directory
     */
    public function create(int $permissions): bool
    {
        return mkdir($this->path, $permissions);
    }

    /**
     * Checks whether the path identifies a directory
     *
     * @return bool True for an existing directory
     */
    public function exists(): bool
    {
        return is_dir($this->path);
    }

    /**
     * Checks whether PHP considers the path writable
     *
     * @return bool Result of the filesystem writability check
     */
    public function isWritable(): bool
    {
        return is_writable($this->path);
    }

    /**
     * Invokes an inspector for every .json filename in directory iteration order
     *
     * @param FileInspector $inspector Callback receiving the folder and each entry basename
     */
    public function scan(FileInspector $inspector): void
    {
        $handle = opendir($this->path);
        while (($file = readdir($handle)) !== false) {
            if ($this->isEntryFile($file)) {
                $inspector->inspect($this->path, $file);
            }
        }
        closedir($handle);
    }

    /**
     * Invokes a deletion algorithm for every .json filename
     *
     * The count follows callback return values. Algorithms may delete physical
     * replicas elsewhere or report their own totals separately.
     *
     * @param FileDeleter $deleter Algorithm receiving the folder and each entry basename
     * @return int Number of callbacks returning true
     */
    public function clear(FileDeleter $deleter): int
    {
        $result = 0;
        $handle = opendir($this->path);
        while (($file = readdir($handle)) !== false) {
            if ($this->isEntryFile($file) && $deleter->delete($this->path, $file)) {
                $result++;
            }
        }
        closedir($handle);
        return $result;
    }

    /**
     * Removes immediate regular files and then the directory itself
     *
     * Includes non-entry files. Child directories are retained and can prevent rmdir().
     */
    public function delete(): void
    {
        $handle = opendir($this->path);
        while (($file = readdir($handle)) !== false) {
            if (!in_array($file, [".", ".."]) && is_file($this->path."/".$file)) {
                unlink($this->path."/".$file);
            }
        }
        closedir($handle);
        rmdir($this->path);
    }

    /**
     * Checks the filename convention used during scans and clears
     *
     * @param string $file Directory entry basename
     * @return bool True when the name ends in .json and is not a dot entry
     */
    private function isEntryFile(string $file): bool
    {
        return !in_array($file, [".", ".."]) && str_ends_with($file, ".json");
    }
}
