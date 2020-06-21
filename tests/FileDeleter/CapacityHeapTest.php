<?php
namespace Test\Lucinda\DB\FileDeleter;
    
use Lucinda\DB\DatabaseEntry;
use Lucinda\DB\FileDeleter\CapacityHeap;
use Lucinda\UnitTest\Result;

class CapacityHeapTest
{
    private $object;
    
    
    
    public function __construct()
    {
        $this->schema = dirname(__DIR__)."/DB";
        $entries = [
            ["tags"=>["a", "b"], "value"=>1, "date"=>"2018-01-02 01:02:03"],
            ["tags"=>["b", "c"], "value"=>2, "date"=>"2018-02-03 04:05:06"],
            ["tags"=>["c", "d"], "value"=>3, "date"=>"2018-03-04 07:08:09"],
            ["tags"=>["d", "e"], "value"=>4, "date"=>"2018-04-05 10:11:12"]
        ];
        foreach ($entries as $info) {
            $object = new DatabaseEntry($this->schema, $info["tags"]);
            $object->set($info["value"]);
            touch($this->schema."/".implode("_", $info["tags"]).".json", strtotime($info["date"]));
        }
        $this->object = new CapacityHeap(2, 3);
        
    }
    

    public function push()
    {
        $this->object->push($this->schema."/a_b.json");
        $this->object->push($this->schema."/b_c.json");
        $this->object->push($this->schema."/c_d.json");
        $this->object->push($this->schema."/d_e.json");
        return new Result(true);
    }
        

    public function getTotalDeleted()
    {
        return new Result($this->object->getTotalDeleted()==2);
    }
        

}
