<?php

namespace Test\Lucinda\DB;

use Lucinda\DB\Schema;
use Test\Lucinda\DB\TestCase;
use Lucinda\DB\Value;
use Lucinda\DB\Key;

class SchemaTest extends TestCase
{
    private $schema;
    private $object;

    public function __construct()
    {
        $this->schema = __DIR__."/DB";
        $this->object = new Schema($this->schema);
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
        $entries = [
            ["tags"=>["a", "b"], "value"=>1],
            ["tags"=>["b", "c"], "value"=>2],
            ["tags"=>["c", "d"], "value"=>3]
        ];
        foreach ($entries as $info) {
            $key = new Key($info["tags"]);
            $object = new Value($this->schema, $key->getValue());
            $object->set($info["value"]);
        }
        return $this->assertEquals(3, $this->object->getCapacity());
    }


    public function getAll()
    {
        return $this->assertEquals(["a_b.json", "b_c.json", "c_d.json"], $this->object->getAll());
    }


    public function getByTag()
    {
        return $this->assertEquals(["a_b.json", "b_c.json"], $this->object->getByTag("b"));
    }

    public function deleteAll()
    {
        return $this->assertEquals(3, $this->object->deleteAll());
    }

    public function populate()
    {
        // create and populate source schema
        $schema = __DIR__."/DB1";
        mkdir($schema, 0777);
        $entries = [
            ["tags"=>["a", "b"], "value"=>1],
            ["tags"=>["b", "c"], "value"=>2],
            ["tags"=>["c", "d"], "value"=>3]
        ];
        foreach ($entries as $info) {
            $key = new Key($info["tags"]);
            $object = new Value($schema, $key->getValue());
            $object->set($info["value"]);
        }

        // populate target based on source
        $this->object->populate($schema);

        // clean and drop source schema
        $files = scandir($schema);
        foreach ($files as $file) {
            if (!in_array($file, [".",".."])) {
                unlink($schema."/".$file);
            }
        }
        rmdir($schema);

        return $this->assertEquals(3, $this->object->getCapacity());
    }

    public function drop()
    {
        $this->object->drop();
        return $this->assertFalse($this->object->exists());
    }
}
