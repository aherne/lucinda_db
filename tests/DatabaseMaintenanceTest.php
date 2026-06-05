<?php

namespace Test\Lucinda\DB;

use Lucinda\DB\DatabaseMaintenance;
use Lucinda\DB\Configuration;
use Test\Lucinda\DB\TestCase;
use Lucinda\DB\Schema;
use Lucinda\DB\SchemaDriver;
use Lucinda\DB\SchemaStatus;

class DatabaseMaintenanceTest extends TestCase
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
        $configuration = new Configuration(__DIR__."/tests.xml", "local");
        $driver = new SchemaDriver($configuration->getSchemas());
        $driver->drop();
    }


    public function checkHealth()
    {
        return $this->assertEquals(
            ["tests/myClient1"=>SchemaStatus::ONLINE, "tests/myClient2"=>SchemaStatus::ONLINE],
            $this->object->checkHealth(0.1)
        );
    }

    public function plugIn()
    {
        $newSchema = "tests/myClient3";

        mkdir($newSchema, 0777);

        $this->object->plugIn($newSchema);

        $output = [];

        $object = new Schema($newSchema);
        $output[] = $this->assertTrue($object->exists(), "added on disk");
        $output[] = $this->assertEquals(4, $object->getCapacity(), "imported capacity");

        $configuration = new Configuration(__DIR__."/tests.xml", "local");
        $output[] = $this->assertEquals(
            ["tests/myClient1", "tests/myClient2", "tests/myClient3"],
            $configuration->getSchemas(),
            "added in XML"
        );

        return $output;
    }


    public function plugOut()
    {
        $newSchema = "tests/myClient3";
        $this->object->plugOut($newSchema);

        $output = [];

        $object = new Schema($newSchema);
        $output[] = $this->assertEquals(0, $object->getCapacity(), "removed from disk");

        $configuration = new Configuration(__DIR__."/tests.xml", "local");
        $output[] = $this->assertEquals(["tests/myClient1", "tests/myClient2"], $configuration->getSchemas(), "removed from XML");

        rmdir($newSchema);

        return $output;
    }


    public function deleteByTag()
    {
        $this->object->deleteByTag("a");

        $configuration = new Configuration(__DIR__."/tests.xml", "local");
        $driver = new SchemaDriver($configuration->getSchemas());
        return $this->assertEquals(["b_c.json", "c_d.json", "d_e.json"], $driver->getAll());
    }


    public function deleteUntil()
    {
        $this->object->deleteUntil(time()-strtotime("2018-02-04 04:05:06"));

        $configuration = new Configuration(__DIR__."/tests.xml", "local");
        $driver = new SchemaDriver($configuration->getSchemas());
        return $this->assertEquals(["c_d.json", "d_e.json"], $driver->getAll());
    }


    public function deleteByCapacity()
    {
        $this->object->deleteByCapacity(1, 2);

        $configuration = new Configuration(__DIR__."/tests.xml", "local");
        $driver = new SchemaDriver($configuration->getSchemas());
        return $this->assertEquals(["d_e.json"], $driver->getAll());
    }
}
