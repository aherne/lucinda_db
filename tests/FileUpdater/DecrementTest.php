<?php

namespace Test\Lucinda\DB\FileUpdater;

use Lucinda\DB\FileUpdater\Decrement;
use Test\Lucinda\DB\TestCase;

class DecrementTest extends TestCase
{
    private $object;
    private $value = 2;

    public function __construct()
    {
        $this->object = new Decrement();
    }

    public function update()
    {
        return $this->assertTrue($this->object->update($this->value));
    }


    public function getValue()
    {
        return $this->assertEquals(1, $this->object->getValue());
    }
}
