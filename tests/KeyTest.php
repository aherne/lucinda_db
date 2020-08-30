<?php
namespace Test\Lucinda\DB;

use Lucinda\DB\Key;
use Lucinda\UnitTest\Result;

class KeyTest
{
    public function getValue()
    {
        $key = new Key(["r","b"]);
        return new Result($key->getValue()=="b_r");
    }
}
