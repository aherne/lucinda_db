<?php

namespace Test\Lucinda\DB\FileInspector;

use Lucinda\DB\FileInspector\All;
use Lucinda\DB\Key;
use Test\Lucinda\DB\TestCase;

class AllTest extends TestCase
{
    private $inspector;

    public function __construct()
    {
        $this->inspector = new All();
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
        return $this->assertEquals(["a_b.json", "b_c.json"], $this->inspector->getEntries());
    }


    public function getEntries()
    {
        return $this->assertEquals(["a_b.json", "b_c.json"], $this->inspector->getEntries());
    }
}
