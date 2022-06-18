<?php

namespace Test\Lucinda\DB;

use Lucinda\DB\Configuration;
use Lucinda\UnitTest\Result;

class ConfigurationTest
{
    public function getSchemas()
    {
        $object = new Configuration(__DIR__."/tests.xml", "local");
        return new Result($object->getSchemas() == ["tests/myClient1", "tests/myClient2"]);
    }


    public function addSchema()
    {
        $object1 = new Configuration(__DIR__."/tests.xml", "local");
        $object1->addSchema("tests/myClient3");
        $object2 = new Configuration(__DIR__."/tests.xml", "local");
        return new Result($object2->getSchemas() == ["tests/myClient1", "tests/myClient2", "tests/myClient3"]);
    }


    public function removeSchema()
    {
        $object1 = new Configuration(__DIR__."/tests.xml", "local");
        $object1->removeSchema("tests/myClient3");
        $object2 = new Configuration(__DIR__."/tests.xml", "local");
        return new Result($object2->getSchemas() == ["tests/myClient1", "tests/myClient2"]);
    }
}
