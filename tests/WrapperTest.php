<?php
namespace Test\Lucinda\DB;

use Lucinda\DB\Wrapper;
use Lucinda\DB\ValueDriver;
use Lucinda\UnitTest\Result;

class WrapperTest
{
    public function getDriver()
    {
        $wrapper = new Wrapper(simplexml_load_string('
<xml>
    <ldb>
        <local>
            <schemas master="myMaster" type="distributed">
                <slave>myClient1</slave>
                <slave>myClient2</slave>
            </schemas>
        </local>
    </ldb>
</xml>
'), "local");
        return new Result($wrapper->getEntryDriver(["a","b"]) instanceof ValueDriver);
    }
}
