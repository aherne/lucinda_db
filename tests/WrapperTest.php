<?php

namespace Test\Lucinda\DB;

use Lucinda\DB\Wrapper;
use Lucinda\DB\ValueDriver;
use Test\Lucinda\DB\TestCase;
use Lucinda\DB\SchemaDriver;

class WrapperTest extends TestCase
{
    private $object;

    public function __construct()
    {
        $this->object = new Wrapper(__DIR__."/tests.xml", "local");
    }

    public function getEntryDriver()
    {
        return $this->assertInstanceOf(ValueDriver::class, $this->object->getEntryDriver(["a","b"]));
    }

    public function getSchemaDriver()
    {
        return $this->assertInstanceOf(SchemaDriver::class, $this->object->getSchemaDriver());
    }
}
