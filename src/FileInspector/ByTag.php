<?php

namespace Lucinda\DB\FileInspector;

use Lucinda\DB\FileInspector;

/**
 * Collects entry basenames whose canonical key contains a complete tag
 *
 * The tag is validated with Key. Matches occur at filename boundaries or
 * underscore separators, preventing a short tag from matching a longer segment.
 * Results accumulate in visit order and are not sorted by this inspector.
 *
 * @see \Lucinda\DB\Folder
 */
final class ByTag implements FileInspector
{
    /**
     * Validated tag matched at key-segment boundaries
     */
    private string $tag;

    /**
     * @var string[] Filenames accumulated across inspections
     */
    private array $entries = [];

    /**
     * Validates and captures the tag used for filename matching
     *
     * @param string $tag One complete lowercase tag segment
     * @throws \Lucinda\DB\KeyException If the tag contains unsupported characters
     */
    public function __construct(string $tag)
    {
        new \Lucinda\DB\Key([$tag]);
        $this->tag = $tag;
    }

    /**
     * Processes one filename supplied by the folder scan
     *
     * Appends the basename only when a complete tag segment matches.
     *
     * @param string $folder Source schema directory
     * @param string $file Entry basename including its .json suffix
     */
    public function inspect(string $folder, string $file): void
    {
        if (preg_match("/(^|_)".preg_quote($this->tag, "/")."(_|\.json)/", $file)==1) {
            $this->entries[] = $file;
        }
    }

    /**
     * Gets the filenames collected so far without clearing the inspector
     *
     * @return string[] Matching basenames in scan order
     */
    public function getEntries(): array
    {
        return $this->entries;
    }
}
