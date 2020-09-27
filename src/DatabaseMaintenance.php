<?php
namespace Lucinda\DB;

use Lucinda\DB\FileDeleter\ByTag as DeleteTag;
use Lucinda\DB\FileDeleter\ByModifiedTime as DeleteByModifiedTime;
use Lucinda\DB\FileDeleter\ByCapacity as DeleteByCapacity;

/**
 * Performs automatic maintenance of LucindaDB schemas:
 *
 * - plugging in schemas based on plugin log
 * - plugging out schemas based on plugout log
 * - reducing size based on various algorithms
 */
class DatabaseMaintenance
{
    const STATUS_ONLINE = 1;
    const STATUS_OFFLINE = 2;
    const STATUS_UNRESPONSIVE = 3;
    const STATUS_OVERLOADED = 4;
    
    private $configuration;
    
    /**
     * Automatically plugs in
     *
     * @param string $xmlFilePath
     * @param string $developmentEnvironment
     */
    public function __construct(string $xmlFilePath, string $developmentEnvironment)
    {
        $this->configuration = new Configuration($xmlFilePath, $developmentEnvironment);
    }
    
    /**
     * Checks schemas health and automatically plugs out those not responsive
     *
     * @param float $maximumWriteDuration Duration in seconds or fractions of seconds.
     * @return array Statuses for each schema plugged
     */
    public function checkHealth(float $maximumWriteDuration): array
    {
        $output = [];
        $schemas = $this->configuration->getSchemas();
        foreach ($schemas as $schema) {
            $object = new Schema($schema);
            if (!$object->exists()) {
                $output[$schema] = self::STATUS_OFFLINE;
            } else {
                $object = new File($schema.DIRECTORY_SEPARATOR."__test__");
                $start = microtime(true);
                $object->write("asd");
                $end = microtime(true);
                if (!$object->exists() || $object->read()!="asd") {
                    $output[$schema] = self::STATUS_UNRESPONSIVE;
                } elseif (($end-$start) > $maximumWriteDuration) {
                    $output[$schema] = self::STATUS_OVERLOADED;
                } else {
                    $output[$schema] = self::STATUS_ONLINE;
                }
                $object->delete();
            }
        }
        return $output;
    }
    
    /**
     * Plugs in schema to DB
     *
     * @param string $schema
     * @throws ConfigurationException
     */
    public function plugIn(string $schema)
    {
        $object = new Schema($schema);
        
        if (!$object->exists($schema)) {
            throw new ConfigurationException("Schema to plug in not found: ".$schema);
        }
        
        if (in_array($schema, $this->configuration->getSchemas())) {
            throw new ConfigurationException("Schema already plugged!");
        }
        
        
        // import files into new schema
        $object->populate($this->configuration->getSchemas()[0]);
        
        // plug in schema in XML
        $this->configuration->addSchema($schema);
        
        // import any remaining files that may have been inserted by different processes as steps above unfolded
        $object->populate($this->configuration->getSchemas()[0]);
    }
    
    /**
     * Plugs out schema from db
     *
     * @param string $schema
     * @throws ConfigurationException
     */
    public function plugOut(string $schema)
    {
        $object = new Schema($schema);
        
        if (!$object->exists()) {
            throw new ConfigurationException("Schema to plug out not found: ".$schema);
        }
        
        if (!in_array($schema, $this->configuration->getSchemas())) {
            throw new ConfigurationException("Schema not plugged!");
        }
        
        // plug out schema in XML
        $this->configuration->removeSchema($schema);
        
        // clear schema of files
        $object->deleteAll();
    }
    
    /**
     * Deletes entries from schemas by tag
     *
     * @param string $tag Tag name
     * @return int Number of entries deleted
     */
    public function deleteByTag(string $tag): int
    {
        $schemas = $this->configuration->getSchemas();
        $folder = new Folder($schemas[rand(0, sizeof($schemas)-1)]);
        return $folder->clear(new DeleteTag($tag, $schemas));
    }
    
    /**
     * Deletes entries in schemas whose last modified time is earlier than input
     *
     * @param int $startTime Unix time starting whom entries won't be deleted
     * @return int Number of entries deleted
     */
    public function deleteUntil(int $startTime): int
    {
        $schemas = $this->configuration->getSchemas();
        $folder = new Folder($schemas[rand(0, sizeof($schemas)-1)]);
        return $folder->clear(new DeleteByModifiedTime($startTime, $schemas));
    }
    
    /**
     * Deletes entries from schemas exceeding maximum capacity based on last modified time.
     *
     * @param int $minCapacity Number of entries allowed to remain if schema reaches max capacity
     * @param int $maxCapacity Maximum number of entries allowed to exist in schema
     * @return int Number of entries deleted
     */
    public function deleteByCapacity(int $minCapacity, int $maxCapacity): int
    {
        $schemas = $this->configuration->getSchemas();
        $fileDeleter = new DeleteByCapacity($schemas, $minCapacity, $maxCapacity);
        $folder = new Folder($schemas[rand(0, sizeof($schemas)-1)]);
        $folder->clear($fileDeleter);
        return $fileDeleter->getTotal();
    }
}
