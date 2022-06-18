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
    private Configuration $configuration;

    /**
     * Automatically plugs in
     *
     * @param  string $xmlFilePath
     * @param  string $developmentEnvironment
     * @throws ConfigurationException If XML is invalid
     */
    public function __construct(string $xmlFilePath, string $developmentEnvironment)
    {
        $this->configuration = new Configuration($xmlFilePath, $developmentEnvironment);
    }

    /**
     * Checks schemas health
     *
     * @param  float $maximumWriteDuration Duration in seconds or fractions of seconds.
     * @return array<string,SchemaStatus> Statuses for each schema plugged
     * @throws \JsonException If values could not be decoded
     */
    public function checkHealth(float $maximumWriteDuration): array
    {
        $output = [];
        $schemas = $this->configuration->getSchemas();
        foreach ($schemas as $schema) {
            $object = new Schema($schema);
            if (!$object->exists()) {
                $output[$schema] = SchemaStatus::OFFLINE;
            } else {
                $object = new File($schema.DIRECTORY_SEPARATOR."__test__");
                $start = microtime(true);
                $object->write("asd");
                $end = microtime(true);
                if (!$object->exists() || $object->read()!="asd") {
                    $output[$schema] = SchemaStatus::UNRESPONSIVE;
                } elseif (($end-$start) > $maximumWriteDuration) {
                    $output[$schema] = SchemaStatus::OVERLOADED;
                } else {
                    $output[$schema] = SchemaStatus::ONLINE;
                }
                $object->delete();
            }
        }
        return $output;
    }

    /**
     * Plugs in schema to DB
     *
     * @param  string $schema
     * @throws ConfigurationException
     */
    public function plugIn(string $schema): void
    {
        $object = new Schema($schema);

        if (!$object->exists()) {
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
     * @param  string $schema
     * @throws ConfigurationException
     */
    public function plugOut(string $schema): void
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
     * @param  string $tag Tag name
     * @return int Number of entries deleted
     */
    public function deleteByTag(string $tag): int
    {
        $schemas = $this->configuration->getSchemas();
        $folder = new Folder($schemas[rand(0, sizeof($schemas)-1)]);
        return $folder->clear(new DeleteTag($tag, $schemas));
    }

    /**
     * Deletes entries in schemas whose last modified time is more than #seconds old
     *
     * @param  int $secondsBeforeNow Number of seconds before now for whom entries will be kept
     * @return int Number of entries deleted
     */
    public function deleteUntil(int $secondsBeforeNow): int
    {
        $schemas = $this->configuration->getSchemas();
        $folder = new Folder($schemas[rand(0, sizeof($schemas)-1)]);
        return $folder->clear(new DeleteByModifiedTime(time()-$secondsBeforeNow, $schemas));
    }

    /**
     * Deletes entries from schemas exceeding maximum capacity based on last modified time.
     *
     * @param  int $minCapacity Number of entries allowed to remain if schema reaches max capacity
     * @param  int $maxCapacity Maximum number of entries allowed to exist in schema
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
