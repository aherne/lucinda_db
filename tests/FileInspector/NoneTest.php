<?php

namespace Test\Lucinda\DB\FileDeleter;

use Lucinda\UnitTest\Result;
use Lucinda\DB\Value;
use Lucinda\DB\Key;
use Lucinda\DB\FileInspector\Counter;

class NoneTest
{
    public function delete()
    {
        $schema = dirname(__DIR__)."/DB";
        $entries = [
            ["tags"=>["a", "b"], "value"=>1, "date"=>"2018-01-02 01:02:03"]
        ];
        $inspector = new Counter();
        foreach ($entries as $info) {
            $object = new Key($info["tags"]);
            $inspector->inspect($schema, $object->getValue().".json");
        }
        return new Result($object->getValue()==1);
    }
}
