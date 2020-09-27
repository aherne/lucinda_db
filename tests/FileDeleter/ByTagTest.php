<?php
namespace Test\Lucinda\DB\FileDeleter;

use Lucinda\DB\Value;
use Lucinda\DB\FileDeleter\ByTag;
use Lucinda\UnitTest\Result;
use Lucinda\DB\Key;

class ByTagTest
{
    public function delete()
    {
        $schema = dirname(__DIR__)."/DB";
        mkdir($schema, 0777);
        $entries = [
            ["tags"=>["a", "b"], "value"=>1, "date"=>"2018-01-02 01:02:03"]
        ];
        foreach ($entries as $info) {
            $key = new Key($info["tags"]);
            $object = new Value($schema, $key->getValue());
            $object->set($info["value"]);
            touch($schema."/".implode("_", $info["tags"]).".json", strtotime($info["date"]));
        }
        $object = new ByTag("a");
        $result = new Result($object->delete($schema, "a_b.json"));
        rmdir($schema);
        return $result;
    }
}
