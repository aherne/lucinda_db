<?php
namespace Test\Lucinda\DB\FileDeleter;
    
use Lucinda\DB\FileDeleter\ByCapacity;
use Lucinda\DB\Key;
use Lucinda\DB\Value;
use Lucinda\UnitTest\Result;

class ByCapacityTest
{
    private $object;
    private $schema;
    
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
            $key = new Key($info["tags"]);
            $object = new Value($this->schema, $key->getValue());
            $object->set($info["value"]);
            touch($this->schema."/".implode("_", $info["tags"]).".json", strtotime($info["date"]));
        }
        $this->object = new ByCapacity(2, 3);
        
    }

    public function delete()
    {
        $this->object->delete($this->schema, "a_b.json");
        $this->object->delete($this->schema, "b_c.json");
        $this->object->delete($this->schema, "c_d.json");
        $this->object->delete($this->schema, "d_e.json");
        return new Result(true);
    }        

    public function getTotal()
    {
        return new Result($this->object->getTotal()==2);
    }
        

}
