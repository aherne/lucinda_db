<?php
namespace Test\Lucinda\DB\FileDeleter;
    
use Lucinda\DB\FileDeleter\None;
use Lucinda\UnitTest\Result;
use Lucinda\DB\DatabaseEntry;

class NoneTest
{
    
    
    public function delete()
    {
        $schema = dirname(__DIR__)."/DB";
        $entries = [
            ["tags"=>["a", "b"], "value"=>1, "date"=>"2018-01-02 01:02:03"]
        ];
        foreach ($entries as $info) {
            $object = new DatabaseEntry($schema, $info["tags"]);
            $object->set($info["value"]);
        }
        $object = new None();
        return new Result($object->delete($schema, "a_b.json"));
    }
        

}
