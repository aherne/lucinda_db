<?php

namespace Test\Lucinda\DB;

use Lucinda\DB\SchemaDriver;
use Lucinda\DB\Configuration;
use Test\Lucinda\DB\TestCase;

class SchemaDriverTest extends TestCase
{
    private $configuration;
    private $object;

    public function __construct()
    {
        $this->configuration = new Configuration(__DIR__."/tests.xml", "local");
        $this->object = new SchemaDriver($this->configuration->getSchemas());
    }

    public function create()
    {
        return $this->assertTrue($this->object->create());
    }


    public function exists()
    {
        return $this->assertTrue($this->object->exists());
    }


    public function getCapacity()
    {
        // fill schema with data
        $entries = [
            ["tags"=>["a", "b"], "value"=>1],
            ["tags"=>["b", "c"], "value"=>2]
        ];
        $schemas = $this->configuration->getSchemas();
        foreach ($schemas as $schema) {
            foreach ($entries as $info) {
                file_put_contents($schema."/". implode("_", $info["tags"]).".json", $info["value"]);
            }
        }
        return $this->assertEquals(2, $this->object->getCapacity());
    }


    public function getAll()
    {
        return $this->assertEquals(["a_b.json", "b_c.json"], $this->object->getAll());
    }


    public function getByTag()
    {
        return $this->assertEquals(["b_c.json"], $this->object->getByTag("c"));
    }


    public function deleteAll()
    {
        return $this->assertEquals(2, $this->object->deleteAll());
    }


    public function drop()
    {
        return $this->assertTrue($this->object->drop());
    }
}
