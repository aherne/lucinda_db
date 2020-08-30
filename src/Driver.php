<?php
namespace Lucinda\DB;

/**
 * Wrapper of Value for data distribution, allowing traffic leveraging on multiple disks
 */
class Driver
{
    private $configuration;
    private $key;
    
    /**
     * Sets up database entry to query based on Configuration object and tags key is composed of
     *
     * @param Configuration $configuration
     * @param array $tags List of tags key is composed of
     */
    public function __construct(Configuration $configuration, array $tags)
    {
        $this->configuration = $configuration;
        
        $object = new Key($tags);
        $this->key = $object->getValue();
    }
    
    /**
     * Sets entry value
     *
     * @param mixed $value
     */
    public function set($value): void
    {
        // update master
        $object = new Value($this->configuration->getMasterSchema(), $this->key);
        $object->set($value);
        
        // update children
        if ($distributionType = $this->configuration->getDistributionType()) {
            $slaveSchemas = $this->configuration->getSlaveSchemas();
            switch ($distributionType) {
                case SchemaDistribution::DISTRIBUTED:
                    $object = new Value($slaveSchemas[crc32($this->key)%sizeof($slaveSchemas)], $this->key);
                    $object->set($value);
                    break;
                case SchemaDistribution::MIRRORED:
                    foreach ($slaveSchemas as $schema) {
                        $object = new Value($schema, $this->key);
                        $object->set($value);
                    }
                    break;
            }
        }
    }
    
    /**
     * Gets existing entry value
     *
     * @throws KeyNotFoundException If entry doesn't exist
     * @return mixed
     */
    public function get()
    {
        // get value
        if ($distributionType = $this->configuration->getDistributionType()) {
            $slaveSchemas = $this->configuration->getSlaveSchemas();
            switch ($distributionType) {
                case SchemaDistribution::DISTRIBUTED:
                    $object = new Value($slaveSchemas[crc32($this->key)%sizeof($slaveSchemas)], $this->key);
                    return $object->get();
                    break;
                case SchemaDistribution::MIRRORED:
                    $object = new Value($slaveSchemas[rand(0, sizeof($slaveSchemas)-1)], $this->key);
                    return $object->get();
                    break;
            }
        } else {
            $object = new Value($this->configuration->getMasterSchema(), $this->key);
            return $object->get();
        }
    }
    
    /**
     * Checks if entry exists
     *
     * @return bool
     */
    public function exists(): bool
    {
        // get value
        if ($distributionType = $this->configuration->getDistributionType()) {
            $slaveSchemas = $this->configuration->getSlaveSchemas();
            switch ($distributionType) {
                case SchemaDistribution::DISTRIBUTED:
                    $object = new Value($slaveSchemas[crc32($this->key)%sizeof($slaveSchemas)], $this->key);
                    return $object->exists();
                    break;
                case SchemaDistribution::MIRRORED:
                    $object = new Value($slaveSchemas[rand(0, sizeof($slaveSchemas)-1)], $this->key);
                    return $object->exists();
                    break;
            }
        } else {
            $object = new Value($this->configuration->getMasterSchema(), $this->key);
            return $object->exists();
        }
    }
    
    /**
     * Increments existing entry and returns value
     *
     * @param int $step Step of incrementation
     * @throws KeyNotFoundException If entry doesn't exist
     * @return int
     */
    public function increment(int $step = 1): int
    {
        // update master
        $object = new Value($this->configuration->getMasterSchema(), $this->key);
        $value = $object->increment($step);
        
        // update children
        if ($distributionType = $this->configuration->getDistributionType()) {
            $slaveSchemas = $this->configuration->getSlaveSchemas();
            switch ($distributionType) {
                case SchemaDistribution::DISTRIBUTED:
                    $object = new Value($slaveSchemas[crc32($this->key)%sizeof($slaveSchemas)], $this->key);
                    $object->set($value);
                    break;
                case SchemaDistribution::MIRRORED:
                    foreach ($slaveSchemas as $schema) {
                        $object = new Value($schema, $this->key);
                        $object->set($value);
                    }
                    break;
            }
        }
        
        return $value;
    }
    
    /**
     * Decrements existing entry and returns value
     *
     * @param int $step Step of decrementation
     * @throws KeyNotFoundException If entry doesn't exist
     * @return int
     */
    public function decrement(int $step = 1): int
    {
        // update master
        $object = new Value($this->configuration->getMasterSchema(), $this->key);
        $value = $object->decrement($step);
        
        // update children
        if ($distributionType = $this->configuration->getDistributionType()) {
            $slaveSchemas = $this->configuration->getSlaveSchemas();
            switch ($distributionType) {
                case SchemaDistribution::DISTRIBUTED:
                    $object = new Value($slaveSchemas[crc32($this->key)%sizeof($slaveSchemas)], $this->key);
                    $object->set($value);
                    break;
                case SchemaDistribution::MIRRORED:
                    foreach ($slaveSchemas as $schema) {
                        $object = new Value($schema, $this->key);
                        $object->set($value);
                    }
                    break;
            }
        }
        
        return $value;
    }
    
    /**
     * Deletes existing entry
     *
     * @throws KeyNotFoundException If entry doesn't exist
     */
    public function delete(): void
    {
        // update master
        $object = new Value($this->configuration->getMasterSchema(), $this->key);
        $object->delete();
        
        // update children
        if ($distributionType = $this->configuration->getDistributionType()) {
            $slaveSchemas = $this->configuration->getSlaveSchemas();
            switch ($distributionType) {
                case SchemaDistribution::DISTRIBUTED:
                    $object = new Value($slaveSchemas[crc32($this->key)%sizeof($slaveSchemas)], $this->key);
                    $object->delete();
                    break;
                case SchemaDistribution::MIRRORED:
                    foreach ($slaveSchemas as $schema) {
                        $object = new Value($schema, $this->key);
                        $object->delete();
                    }
                    break;
            }
        }
    }
}
