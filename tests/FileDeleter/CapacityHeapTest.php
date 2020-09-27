<?php
namespace Test\Lucinda\DB\FileDeleter;

use Lucinda\DB\Value;
use Lucinda\DB\FileDeleter\CapacityHeap;
use Lucinda\UnitTest\Result;
use Lucinda\DB\Key;

class CapacityHeapTest
{
    private $object;
    private $schema;
    
    public function __construct()
    {
        $this->schema = dirname(__DIR__)."/DB";
        mkdir($this->schema, 0777);
        $entries = [
            ["tags"=>["a", "b"], "value"=>1, "date"=>"2018-01-02 01:02:03"],
            ["tags"=>["b", "c"], "value"=>2, "date"=>"2018-02-03 04:05:06"],
            ["tags"=>["c", "d"], "value"=>3, "date"=>"2018-03-04 07:08:09"],
            ["tags"=>["d", "e"], "value"=>4, "date"=>"2018-04-05 10:11:12"]
        ];
        foreach ($entries as $info) {
            $key = new Key($info["tags"]);
            $object = new Value($this->schema, $key->getValue());
            $object->set($info["value"]);
            touch($this->schema."/".implode("_", $info["tags"]).".json", strtotime($info["date"]));
        }
        $this->object = new CapacityHeap([$this->schema], 2, 3);
    }
    
    public function __destruct()
    {
        $files = scandir($this->schema);
        foreach ($files as $file) {
            if (!in_array($file, [".",".."])) {
                unlink($this->schema."/".$file);
            }
        }
        rmdir($this->schema);
    }
    

    public function push()
    {
        $this->object->push("a_b.json");
        $this->object->push("b_c.json");
        $this->object->push("c_d.json");
        $this->object->push("d_e.json");
        return new Result(true);
    }
        

    public function getTotalDeleted()
    {
        return new Result($this->object->getTotalDeleted()==2);
    }
}
