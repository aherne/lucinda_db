<?php

namespace Test\Lucinda\DB\FileUpdater;

use Lucinda\DB\FileUpdater\Decrement;
use Lucinda\UnitTest\Result;

class DecrementTest
{
    private $object;
    private $value = 2;

    public function __construct()
    {
        $this->object = new Decrement();
    }

    public function update()
    {
        return new Result($this->object->update($this->value));
    }


    public function getValue()
    {
        return new Result($this->object->getValue()==1);
    }
}
