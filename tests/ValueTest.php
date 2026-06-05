<?php

namespace Test\Lucinda\DB;

use Lucinda\DB\Value;
use Test\Lucinda\DB\TestCase;
use Lucinda\DB\Key;

class ValueTest extends TestCase
{
    private $object;

    public function __construct()
    {
        $schema = __DIR__."/DB";
        mkdir($schema, 0777);

        $key =  new Key(["a","b"]);
        $this->object = new Value($schema, $key->getValue());
    }

    public function __destruct()
    {
        rmdir(__DIR__."/DB");
    }

    public function set()
    {
        $this->object->set(1);
        return $this->assertTrue($this->object->exists());
    }


    public function get()
    {
        return $this->assertEquals(1, $this->object->get());
    }


    public function exists()
    {
        return $this->assertTrue($this->object->exists());
    }


    public function increment()
    {
        return $this->assertEquals(2, $this->object->increment());
    }


    public function decrement()
    {
        return $this->assertEquals(1, $this->object->decrement());
    }


    public function delete()
    {
        $this->object->delete();
        return $this->assertFalse($this->object->exists());
    }
}
