<?php
namespace Test\Lucinda\DB\FileDeleter;

use Lucinda\DB\FileDeleter\ByModifiedTime;
use Lucinda\DB\Key;
use Lucinda\DB\Value;
use Lucinda\UnitTest\Result;

class ByModifiedTimeTest
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
        $object = new ByModifiedTime(strtotime("2018-01-23 10:11:22"), [$schema]);
        $result = new Result($object->delete($schema, "a_b.json"));
        rmdir($schema);
        return $result;
    }
}
