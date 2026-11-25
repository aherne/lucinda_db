<?php

namespace Lucinda\DB;

/**
 * Creates entry and schema drivers from application XML
 *
 * Construction delegates XML validation to Configuration and retains its schema
 * snapshot. Each factory method creates a driver using those paths; construction
 * does not create folders or read entry files. Environment selection and XML
 * loading belong to the caller.
 *
 * A driver created before maintenance changes keeps its original replica list.
 * Load the updated XML and construct another wrapper to use the new topology.
 *
 * @see Configuration
 * @see ValueDriver
 * @see SchemaDriver
 */
final class Wrapper
{
    /**
     * Validated schema snapshot used when constructing drivers
     */
    private Configuration $configuration;

    /**
     * Captures the replica configuration from caller-supplied XML
     *
     * @param \SimpleXMLElement $xml Application document containing lucinda_db/schemas/schema
     * @throws ConfigurationException If the database section or schemas are missing
     */
    public function __construct(\SimpleXMLElement $xml)
    {
        $this->configuration = new Configuration($xml);
    }

    /**
     * Creates a replicated entry driver for an unordered collection of tags
     *
     * Tag validation and canonical key generation occur when the driver is created.
     * This operation does not check whether an entry exists.
     *
     * @param string[] $tags Nonempty list of lowercase letters, digits, and hyphens
     * @return ValueOperations New driver bound to the derived key and configured replicas
     * @throws KeyException If the tags are empty or contain unsupported characters
     */
    public function getEntryDriver(array $tags): ValueOperations
    {
        return new ValueDriver($this->configuration->getSchemas(), $tags);
    }

    /**
     * Creates a driver operating on the configured replica folders
     *
     * @return SchemaOperations New driver sharing this wrapper's schema snapshot
     */
    public function getSchemaDriver(): SchemaOperations
    {
        return new SchemaDriver($this->configuration->getSchemas());
    }
}
