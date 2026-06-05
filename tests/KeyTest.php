<?php

namespace Test\Lucinda\DB;

use Lucinda\DB\Key;
use Test\Lucinda\DB\TestCase;

class KeyTest extends TestCase
{
    public function getValue()
    {
        $key = new Key(["r","b"]);
        return $this->assertEquals("b_r", $key->getValue());
    }
}
