<?php

namespace Lucinda\DB;

/**
 * Extracts an ordered replica list from caller-supplied application XML
 *
 * The root must contain lucinda_db/schemas/schema without an environment layer.
 * Construction snapshots the configured paths and rejects a missing database
 * section or an empty schema list. Paths retain their original order and spelling;
 * relative paths are resolved by filesystem operations against the working directory.
 *
 * This object neither loads nor saves XML files. The caller selects an environment
 * and parses XML before construction; DatabaseMaintenance owns persistent edits.
 *
 * @see Wrapper
 * @see DatabaseMaintenance
 */
final class Configuration
{
    /**
     * @var string[] Ordered schema paths extracted from XML
     */
    private array $schemas = [];

    /**
     * Parses the database section of an already loaded XML document
     *
     * Expects the application root, rather than the lucinda_db element itself.
     *
     * @param \SimpleXMLElement $xmlRoot Application document containing lucinda_db/schemas
     * @throws ConfigurationException If the database section or schema list is missing
     */
    public function __construct(\SimpleXMLElement $xmlRoot)
    {
        $xml = $xmlRoot->lucinda_db;
        if (empty($xml)) {
            throw new ConfigurationException("Database not configured!");
        }

        $this->setSchemas($xml);
    }

    /**
     * Copies schema paths from the schemas element in document order
     *
     * @param \SimpleXMLElement $xml Database section containing schemas/schema
     * @throws ConfigurationException If no schema elements are configured
     */
    private function setSchemas(\SimpleXMLElement $xml): void
    {
        $this->schemas = (array) $xml->schemas->schema;
        if (empty($this->schemas)) {
            throw new ConfigurationException("No schemas defined!");
        }
    }


    /**
     * Gets the configured replica paths in their original order
     *
     * Returns the construction-time snapshot. No path normalization or filesystem
     * validation is performed.
     *
     * @return string[] Configured paths, with the first path used for counter updates
     */
    public function getSchemas(): array
    {
        return $this->schemas;
    }
}
