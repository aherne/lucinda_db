<?php
namespace Test\Lucinda\DB;

use Lucinda\DB\DatabaseMaintenance;
use Lucinda\DB\Configuration;
use Lucinda\UnitTest\Result;
use Lucinda\DB\Schema;
use Lucinda\DB\SchemaDriver;

class DatabaseMaintenanceTest
{
    private $object;
    
    public function __construct()
    {
        $this->object = new DatabaseMaintenance(__DIR__."/tests.xml", "local");
        
        // create and fill database
        $entries = [
            ["tags"=>["a", "b"], "value"=>1, "date"=>"2018-01-02 01:02:03"],
            ["tags"=>["b", "c"], "value"=>2, "date"=>"2018-02-03 04:05:06"],
            ["tags"=>["c", "d"], "value"=>3, "date"=>"2018-03-04 07:08:09"],
            ["tags"=>["d", "e"], "value"=>4, "date"=>"2018-04-05 10:11:12"]
        ];
        $configuration = new Configuration(__DIR__."/tests.xml", "local");
        $schemas = $configuration->getSchemas();
        foreach ($schemas as $schema) {
            mkdir($schema, 0777);
            foreach ($entries as $info) {
                $file = $schema."/".implode("_", $info["tags"]).".json";
                file_put_contents($file, $info["value"]);
                touch($file, strtotime($info["date"]));
            }
        }
    }
    public function __destruct()
    {
        $driver = new SchemaDriver(new Configuration(__DIR__."/tests.xml", "local"));
        $driver->drop();
    }
    
    
    public function checkHealth()
    {
        return new Result($this->object->checkHealth(0.1)==["tests/myClient1"=>DatabaseMaintenance::STATUS_ONLINE, "tests/myClient2"=>DatabaseMaintenance::STATUS_ONLINE]);
    }

    public function plugIn()
    {
        $newSchema = "tests/myClient3";
        
        mkdir($newSchema, 0777);
        
        $this->object->plugIn($newSchema);
        
        $output = [];
        
        $object = new Schema($newSchema);
        $output[] = new Result($object->exists() && $object->getCapacity()==4, "added on disk");
        
        $configuration = new Configuration(__DIR__."/tests.xml", "local");
        $output[] = new Result($configuration->getSchemas()==["tests/myClient1", "tests/myClient2", "tests/myClient3"], "added in XML");
        
        return $output;
    }
        

    public function plugOut()
    {
        $newSchema = "tests/myClient3";
        $this->object->plugOut($newSchema);
        
        $output = [];
        
        $object = new Schema($newSchema);
        $output[] = new Result($object->getCapacity()==0, "removed from disk");
        
        $configuration = new Configuration(__DIR__."/tests.xml", "local");
        $output[] = new Result($configuration->getSchemas()==["tests/myClient1", "tests/myClient2"], "removed from XML");
        
        rmdir($newSchema);
        
        return $output;
    }
        

    public function deleteByTag()
    {
        $this->object->deleteByTag("a");
        
        $schemaDriver = new SchemaDriver(new Configuration(__DIR__."/tests.xml", "local"));
        return new Result($schemaDriver->getAll()==["b_c.json", "c_d.json", "d_e.json"]);
    }
        

    public function deleteUntil()
    {
        $this->object->deleteUntil(strtotime("2018-02-04 04:05:06"));
        
        $schemaDriver = new SchemaDriver(new Configuration(__DIR__."/tests.xml", "local"));
        return new Result($schemaDriver->getAll()==["c_d.json", "d_e.json"]);
    }
        

    public function deleteByCapacity()
    {
        $this->object->deleteByCapacity(1, 2);
                
        $schemaDriver = new SchemaDriver(new Configuration(__DIR__."/tests.xml", "local"));
        return new Result($schemaDriver->getAll()==["d_e.json"]);
    }
}
