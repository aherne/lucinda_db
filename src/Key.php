<?php

namespace Lucinda\DB;

/**
 * Derives a deterministic entry key from validated tags
 *
 * At least one tag is required. Each tag consists of lowercase ASCII letters,
 * digits, or hyphens. Tags are sorted and joined with underscores so the same
 * unordered list produces the same filename stem. Repeated tags are preserved.
 *
 * This object produces the stem only; Value appends the .json extension.
 *
 * @see Value
 * @see KeyException
 */
final class Key
{
    /**
     * Canonical filename stem generated during construction
     */
    private string $value;

    /**
     * Validates and sorts tags to build an immutable key
     *
     * @param string[] $tags Nonempty collection of supported tag strings
     * @throws KeyException If the collection is empty or a tag contains unsupported characters
     */
    public function __construct(array $tags)
    {
        $this->setValue($tags);
    }

    /**
     * Validates each tag and stores their sorted underscore-joined representation
     *
     * @param string[] $tags Tags from which to derive the filename stem
     * @throws KeyException If no tags are supplied or a tag fails validation
     */
    private function setValue(array $tags): void
    {
        if (empty($tags)) {
            throw new KeyException("Tags cannot be empty!");
        }
        // validate
        $tags = array_map(
            function ($value) {
                if (preg_match("/^[a-z0-9\-]+$/", $value)==0) {
                    throw new KeyException("Tags can contain only lowercase alphanumeric characters!");
                }
                return $value;
            },
            $tags
        );
        // sort, if number of elements is more than one
        if (count($tags)>1) {
            sort($tags);
        }
        $this->value = implode("_", $tags);
    }

    /**
     * Gets the canonical filename stem without the .json suffix
     *
     * @return string Sorted tags joined with underscores
     */
    public function getValue(): string
    {
        return $this->value;
    }
}
