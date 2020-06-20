<?php
namespace Test\Lucinda\DB;
    
use Lucinda\DB\Key;
use Lucinda\UnitTest\Result;

class KeyTest
{

    public function getValue()
    {
        $key = new Key(["A","b"]);
        return new Result($key->getValue()=="a_b");
    }
        

}
