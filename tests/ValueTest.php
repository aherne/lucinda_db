<?php

namespace Test\Lucinda\DB;

use Lucinda\DB\Value;
use Lucinda\UnitTest\Result;
use Lucinda\DB\Key;

class ValueTest
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
        return new Result(true);
    }


    public function get()
    {
        return new Result($this->object->get()==1);
    }


    public function exists()
    {
        return new Result($this->object->exists());
    }


    public function increment()
    {
        return new Result($this->object->increment()==2);
    }


    public function decrement()
    {
        return new Result($this->object->decrement()==1);
    }


    public function delete()
    {
        $this->object->delete();
        return new Result(!$this->object->exists());
    }
}
