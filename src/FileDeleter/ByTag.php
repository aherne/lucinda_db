<?php

namespace Lucinda\DB\FileDeleter;

use Lucinda\DB\FileDeleter;

/**
 * Deletes visited entries whose key contains a complete validated tag
 *
 * Matches whole underscore-separated segments or filename boundaries. With a
 * replica list, matching filenames are removed from each replica; otherwise the
 * scanned folder is used. Deletions bypass File mutexes and include legacy locks.
 */
final class ByTag implements FileDeleter
{
    /**
     * Validated tag used for complete-segment matching
     */
    private string $tag;

    /**
     * @var string[] Replica directories targeted by a matching deletion
     */
    private array $replicas = [];

    /**
     * Validates a tag and captures the optional replica deletion scope
     *
     * @param string $tag Complete lowercase tag segment to match
     * @param string[] $replicas Replica paths, or an empty list to use the scanned folder
     * @throws \Lucinda\DB\KeyException If the tag is invalid
     */
    public function __construct(string $tag, array $replicas = [])
    {
        new \Lucinda\DB\Key([$tag]);
        $this->tag = $tag;
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
        if (!in_array($file, [".", ".."]) && preg_match("/(^|_)".preg_quote($this->tag, "/")."(_|\.json)/", $file)==1) {
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
