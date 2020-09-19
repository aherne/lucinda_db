<?php
namespace Lucinda\DB;

/**
 * Encapsulates LucindaDB database configuration by XML "ldb" tag and development environment
 */
class Configuration
{
    private $xmlFilePath;
    private $developmentEnvironment;
    
    private $schemas = [];
    
    /**
     * Reads XML file for schema and distribution policies.
     *
     * @param string $xmlFilePath Path to XML configuration file
     * @param string $developmentEnvironment Value of development environment
     * @throws ConfigurationException If XML is improperly formatted
     */
    public function __construct(string $xmlFilePath, string $developmentEnvironment)
    {
        if(!file_exists($xmlFilePath)) {
            throw new ConfigurationException("Configuration file not found!");
        }
        $this->xmlFilePath = $xmlFilePath;
        $this->developmentEnvironment = $developmentEnvironment;
        
        $xmlRoot = \simplexml_load_file($xmlFilePath);
        $xml = $xmlRoot->lucinda_db->{$developmentEnvironment};
        if (empty($xml)) {
            throw new ConfigurationException("Database not configured for environment: ".$developmentEnvironment."!");
        }
        
        $this->setSchemas($xml);
    }
    
    private function setSchemas(\SimpleXMLElement $xml): void
    {
        $this->schemas = (array) $xml->schemas->schema;
        if (empty($this->schemas)) {
            throw new ConfigurationException("No schemas defined!");
        }
    }
    
    
    /**
     * Gets absolute paths to schemas
     *
     * @return string[]
     */
    public function getSchemas(): array
    {
        return $this->slaveSchemas;
    }
    
    public function plugIn(string $schema): void
    {
        $xmlRoot = \simplexml_load_file($this->xmlFilePath);
        $parent = $xmlRoot->lucinda_db->{$this->developmentEnvironment}->schemas;
        $parent->addChild("schema", $schema);
        $xmlRoot->asXML($this->xmlFilePath);
        
        $this->schemas[] = $schema;
    }
    
    public function plugOut(string $schema): void
    {
        $xmlRoot = \simplexml_load_file($this->xmlFilePath);
        $parent = $xmlRoot->lucinda_db->{$this->developmentEnvironment}->schemas;
        foreach($parent as $i=>$child) {
            if((string) $child == $schema) {
                unset($parent->schema[$i]); 
                break; 
            }
        }
        $xmlRoot->asXML($this->xmlFilePath);
        
        foreach ($this->schemas as $i=>$foundSchema) {
            if($foundSchema == $schema) {
                unset($this->schemas[$i]);
            }
        }
    }
}
