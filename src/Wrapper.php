<?php
namespace Lucinda\DB;

/**
 * Encapsulates operations with LucindaDB entries
 */
class Wrapper
{
    private $configuration;
    
    /**
     * Reads XML file for schema and distribution policies into Configuration object
     *
     * @param string $xmlFilePath Path to XML configuration file
     * @param string $developmentEnvironment Value of development environment
     * @throws ConfigurationException If XML is improperly formatted
     */
    public function __construct(string $xmlFilePath, string $developmentEnvironment)
    {
        $this->configuration = new Configuration($xmlFilePath, $developmentEnvironment);
    }
    
    /**
     * Gets instance to be used in working with distributed database entries
     *
     * @param array $tags List of tags key is composed of
     * @return ValueDriver Instance to be used in querying database
     */
    public function getEntryDriver(array $tags): ValueDriver
    {
        return new ValueDriver($this->configuration, $tags);
    }
    
    /**
     * Gets instance to be used in working with distributed database schemas
     *
     * @return SchemaDriver Instance to be used in querying schemas
     */
    public function getSchemaDriver(): SchemaDriver
    {
        return new SchemaDriver($this->configuration);
    }
}
