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
     * @param \SimpleXMLElement $xml XML root where ldb tag must be a child
     * @param string $developmentEnvironment Value of development environment
     * @throws ConfigurationException If XML is improperly formatted
     */
    public function __construct(\SimpleXMLElement $xml, string $developmentEnvironment)
    {
        $this->configuration = new Configuration($xml, $developmentEnvironment);
    }
    
    /**
     * Gets instance to be used in working with database entries
     *
     * @param array $tags List of tags key is composed of
     * @return ValueDriver Instance to be used in querying database
     */
    public function getEntryDriver(array $tags): ValueDriver
    {
        return new ValueDriver($this->configuration, $tags);
    }
    
    public function getSchemaDriver(string $cacheLocation="schemas.log"): SchemaDriver
    {
        return new SchemaDriver($this->configuration, $cacheLocation);
    }
}
