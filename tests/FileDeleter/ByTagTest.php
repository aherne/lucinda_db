<?php
namespace Test\Lucinda\DB\FileDeleter;
    
use Lucinda\DB\DatabaseEntry;
use Lucinda\DB\FileDeleter\ByTag;
use Lucinda\UnitTest\Result;

class ByTagTest
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
            touch($schema."/".implode("_", $info["tags"]).".json", strtotime($info["date"]));
        }
        $object = new ByTag("a");
        return new Result($object->delete($schema, "a_b.json"));
    }
        

}
