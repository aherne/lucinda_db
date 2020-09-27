<?php
namespace Test\Lucinda\DB;

use Lucinda\DB\Schema;
use Lucinda\UnitTest\Result;
use Lucinda\DB\Value;
use Lucinda\DB\Key;

class SchemaTest
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
        return new Result($this->object->create());
    }

    public function exists()
    {
        return new Result($this->object->exists());
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
        return new Result($this->object->getCapacity()==3);
    }
    
    
    public function getAll()
    {
        return new Result($this->object->getAll()==["a_b.json", "b_c.json", "c_d.json"]);
    }
    
    
    public function getByTag()
    {
        return new Result($this->object->getByTag("b")==["a_b.json", "b_c.json"]);
    }

    public function deleteAll()
    {
        return new Result($this->object->deleteAll()==3);
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
                
        return new Result($this->object->getCapacity()==3);
    }

    public function drop()
    {
        $this->object->drop();
        return new Result(!$this->object->exists());
    }
}
