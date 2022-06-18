<?php

namespace Test\Lucinda\DB;

use Lucinda\DB\Wrapper;
use Lucinda\DB\ValueDriver;
use Lucinda\UnitTest\Result;
use Lucinda\DB\SchemaDriver;

class WrapperTest
{
    private $object;

    public function __construct()
    {
        $this->object = new Wrapper(__DIR__."/tests.xml", "local");
    }

    public function getEntryDriver()
    {
        return new Result($this->object->getEntryDriver(["a","b"]) instanceof ValueDriver);
    }

    public function getSchemaDriver()
    {
        return new Result($this->object->getSchemaDriver() instanceof SchemaDriver);
    }
}
