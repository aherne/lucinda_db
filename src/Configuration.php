<?php
namespace Lucinda\DB;

/**
 * Encapsulates LucindaDB database configuration by XML "ldb" tag and development environment
 */
class Configuration
{
    private $masterSchema;
    private $distributionType;
    private $slaveSchemas = [];
    
    /**
     * Reads XML file for schema and distribution policies.
     *
     * @param \SimpleXMLElement $xml XML root where ldb tag must be a child
     * @param string $developmentEnvironment Value of development environment
     * @throws ConfigurationException If XML is improperly formatted
     */
    public function __construct(\SimpleXMLElement $xml, string $developmentEnvironment)
    {
        $xml = $xml->ldb->{$developmentEnvironment};
        if (!empty($xml)) {
            $masterSchema = (string) $xml->schemas["master"];
            if (!$masterSchema) {
                throw new ConfigurationException("Master schema is mandatory");
            }
            $this->masterSchema = $masterSchema;
            
            $distributionType = (string) $xml->schemas["type"];
            if ($distributionType) {
                if ($distributionType!=SchemaDistribution::DISTRIBUTED && $distributionType!=SchemaDistribution::MIRRORED) {
                    throw new ConfigurationException("Unknown schema distribution algorithm: ".$distributionType);
                }
                $this->distributionType = $distributionType;
                
                $this->slaveSchemas = (array) $xml->schemas->slave;
                if (empty($this->slaveSchemas)) {
                    throw new ConfigurationException("No slave schema defined!");
                }
            }
        }
    }
    
    /**
     * Gets absolute path to master schema containing DB entries
     *
     * @return string
     */
    public function getMasterSchema(): string
    {
        return $this->masterSchema;
    }
    
    /**
     * Gets slaves data distribution type chosen:
     * - distributed: entry will be persisted on random slave (promoting load balancing)
     * - mirrored: entry will be persisted on every slave (promoting high availability)
     *
     * @return string
     */
    public function getDistributionType(): ?string
    {
        return $this->distributionType;
    }
    
    /**
     * Gets absolute paths to slave schemas
     *
     * @return string[]
     */
    public function getSlaveSchemas(): array
    {
        return $this->slaveSchemas;
    }
}
