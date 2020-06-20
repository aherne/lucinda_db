<?php
namespace Lucinda\DB;

/**
 * Encapsulates key creation based on composing tags 
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
        // validate
        array_map(function($value) {
            if (!is_string($value) || preg_match("/^[a-zA-Z0-9\-]+$/", $value)==0) {
                throw new KeyException("Tags can only be alphanumeric!");
            }
            return $value;
        }, $tags);
        // sort, if number of elements is more than one
        if(count($tags)>1) {
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