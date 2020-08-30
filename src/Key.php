<?php
namespace Lucinda\DB;

/**
 * Encapsulates key in KV store based on composing tags
 */
class Key
{
    private $value;
    
    /**
     * Constructs key based on composing tags
     *
     * @param array $tags List of tags key is composed of
     */
    public function __construct(array $tags)
    {
        $this->setValue($tags);
    }
    
    /**
     * Creates key name based on composing tags
     *
     * @param array $tags List of tags key is composed of
     * @throws KeyException If one of tags is not alphanumeric
     */
    private function setValue(array $tags): void
    {
        if (empty($tags)) {
            throw new KeyException("Tags cannot be empty!");
        }
        // validate
        $tags = array_map(function ($value) {
            if (preg_match("/^[a-z0-9\-]+$/", $value)==0) {
                throw new KeyException("Tags can contain only lowercase alphanumeric characters!");
            }
            return $value;
        }, $tags);
        // sort, if number of elements is more than one
        if (count($tags)>1) {
            sort($tags);
        }
        $this->value = implode("_", $tags);
    }
    
    /**
     * Gets key name created
     *
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }
}
