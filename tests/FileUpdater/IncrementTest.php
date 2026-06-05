<?php

namespace Test\Lucinda\DB\FileUpdater;

use Lucinda\DB\FileUpdater\Increment;
use Test\Lucinda\DB\TestCase;

class IncrementTest extends TestCase
{
    private $object;
    private $value = 1;

    public function __construct()
    {
        $this->object = new Increment();
    }

    public function update()
    {
        return $this->assertTrue($this->object->update($this->value));
    }


    public function getValue()
    {
        return $this->assertEquals(2, $this->object->getValue());
    }
}
