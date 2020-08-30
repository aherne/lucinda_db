<?php
namespace Lucinda\DB;

/**
 * Encapsulates a json file and its operations
 */
class File
{
    private $path;
    
    /**
     * Constructs file by absolute path.
     *
     * @param string $path Absolute path.
     */
    public function __construct(string $path)
    {
        $this->path = $path;
    }
    
    /**
     * Writes file to disk
     *
     * @param mixed $value Value to save as JSON
     * @throws JsonException If value could not be encoded
     */
    public function write($value): void
    {
        $val = json_encode($value);
        if ($val === false) {
            throw new JsonException("Value cannot be encoded!");
        }
        file_put_contents($this->path, $val);
    }
    
    /**
     * Checks if file exists
     *
     * @return bool
     */
    public function exists(): bool
    {
        return file_exists($this->path);
    }
    
    /**
     * Reads file and returns value
     *
     * @throws JsonException If value could not be decoded
     * @return mixed
     */
    public function read()
    {
        $json = json_decode(file_get_contents($this->path), true);
        if ($json === false) {
            throw new JsonException("Value cannot be decoded: ".$this->path);
        }
        return $json;
    }
    
    /**
     * Updates value in file by callback using exclusive lock to insure writes synchronization
     *
     * WARNING: this operation is protected by a mutex, but in order to to prevent deadlocks it doesn't wait if mutex could not be acquired
     *
     * @param FileUpdater $callback Encapsulates algorithm of file value update
     * @throws JsonException If value could not be decoded
     * @throws LockException If mutex could not be acquired.
     */
    public function update(FileUpdater $callback)
    {
        $handle = fopen($this->path, "a+");
        try {
            if (flock($handle, LOCK_EX)) {
                try {
                    $json = [];
                    $index = fgets($handle);
                    if ($index) {
                        $json = json_decode($index, true);
                        if ($json === false) {
                            throw new JsonException("Value cannot be decoded: ".$this->path);
                        }
                    }
                    $changed = $callback->update($json);
                    if ($changed) {
                        ftruncate($handle, 0);
                        fwrite($handle, json_encode($json));
                    }
                } finally {
                    fflush($handle);
                    flock($handle, LOCK_UN);
                }
            } else {
                throw new LockException("Lock already active on: ".$this->path);
            }
        } finally {
            fclose($handle);
        }
    }
    
    /**
     * Deletes file from disk
     */
    public function delete()
    {
        unlink($this->path);
    }
}
