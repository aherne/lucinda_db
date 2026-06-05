<?php

namespace Test\Lucinda\DB;

use Lucinda\DB\Configuration;
use Test\Lucinda\DB\TestCase;

class ConfigurationTest extends TestCase
{
    public function getSchemas()
    {
        $object = new Configuration(__DIR__."/tests.xml", "local");
        return $this->assertEquals(["tests/myClient1", "tests/myClient2"], $object->getSchemas());
    }


    public function addSchema()
    {
        $object1 = new Configuration(__DIR__."/tests.xml", "local");
        $object1->addSchema("tests/myClient3");
        $object2 = new Configuration(__DIR__."/tests.xml", "local");
        return $this->assertEquals(["tests/myClient1", "tests/myClient2", "tests/myClient3"], $object2->getSchemas());
    }


    public function removeSchema()
    {
        $object1 = new Configuration(__DIR__."/tests.xml", "local");
        $object1->removeSchema("tests/myClient3");
        $object2 = new Configuration(__DIR__."/tests.xml", "local");
        return $this->assertEquals(["tests/myClient1", "tests/myClient2"], $object2->getSchemas());
    }
}
