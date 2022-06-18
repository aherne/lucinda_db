<?php

namespace Test\Lucinda\DB;

use Lucinda\DB\SchemaDriver;
use Lucinda\DB\Configuration;
use Lucinda\UnitTest\Result;

class SchemaDriverTest
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
        return new Result($this->object->create());
    }


    public function exists()
    {
        return new Result($this->object->exists());
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
        return new Result($this->object->getCapacity()==2);
    }


    public function getAll()
    {
        return new Result($this->object->getAll()==["a_b.json", "b_c.json"]);
    }


    public function getByTag()
    {
        return new Result($this->object->getByTag("c")==["b_c.json"]);
    }


    public function deleteAll()
    {
        return new Result($this->object->deleteAll()==2);
    }


    public function drop()
    {
        return new Result($this->object->drop());
    }
}
