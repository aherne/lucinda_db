<?php

namespace Test\Lucinda\DB\FileInspector;

use Lucinda\DB\FileInspector\Counter;
use Lucinda\DB\Key;
use Lucinda\UnitTest\Result;

class CounterTest
{
    private $inspector;

    public function __construct()
    {
        $this->inspector = new Counter();
    }

    public function inspect()
    {
        $schema = dirname(__DIR__)."/DB";
        $entries = [
            ["a", "b"],
            ["b", "c"],
        ];
        foreach ($entries as $info) {
            $object = new Key($info);
            $this->inspector->inspect($schema, $object->getValue().".json");
        }
        return new Result(true);
    }


    public function getValue()
    {
        return new Result($this->inspector->getValue()==2);
    }
}
