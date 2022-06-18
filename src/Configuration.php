<?php

namespace Lucinda\DB;

/**
 * Encapsulates LucindaDB database configuration by XML "ldb" tag and development environment
 */
class Configuration
{
    private string $xmlFilePath;
    private string $developmentEnvironment;
    /**
     * @var string[]
     */
    private array $schemas = [];

    /**
     * Reads XML file for schema and distribution policies.
     *
     * @param  string $xmlFilePath            Path to XML configuration file
     * @param  string $developmentEnvironment Value of development environment
     * @throws ConfigurationException If XML is improperly formatted
     */
    public function __construct(string $xmlFilePath, string $developmentEnvironment)
    {
        if (!file_exists($xmlFilePath)) {
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

    /**
     * Set schemas information based on contents of <schemas> tag
     *
     * @param  \SimpleXMLElement $xml
     * @throws ConfigurationException
     */
    private function setSchemas(\SimpleXMLElement $xml): void
    {
        $this->schemas = (array) $xml->schemas->schema;
        if (empty($this->schemas)) {
            throw new ConfigurationException("No schemas defined!");
        }
    }


    /**
     * Gets absolute paths to XML
     *
     * @return string[]
     */
    public function getSchemas(): array
    {
        return $this->schemas;
    }

    /**
     * Plugs in schema in configuration.
     *
     * @param string $schema
     */
    public function addSchema(string $schema): void
    {
        $xmlRoot = \simplexml_load_file($this->xmlFilePath);
        $parent = $xmlRoot->lucinda_db->{$this->developmentEnvironment}->schemas;
        $parent->addChild("schema", $schema);

        // beautify xml
        $domxml = new \DOMDocument('1.0');
        $domxml->preserveWhiteSpace = false;
        $domxml->formatOutput = true;
        $domxml->loadXML($xmlRoot->asXML());
        $domxml->save($this->xmlFilePath);

        $this->schemas[] = $schema;
    }

    /**
     * Plugs out schema in XML
     *
     * @param string $schema
     */
    public function removeSchema(string $schema): void
    {
        $xmlRoot = \simplexml_load_file($this->xmlFilePath);
        $parent = $xmlRoot->lucinda_db->{$this->developmentEnvironment}->schemas;
        foreach ($parent->schema as $i=>$child) {
            if ((string) $child == $schema) {
                $dom=dom_import_simplexml($child);
                $dom->parentNode->removeChild($dom);
                break;
            }
        }
        $xmlRoot->asXML($this->xmlFilePath);

        foreach ($this->schemas as $i=>$foundSchema) {
            if ($foundSchema == $schema) {
                unset($this->schemas[$i]);
            }
        }
    }
}
