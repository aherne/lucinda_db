<?php
namespace Test\Lucinda\DB;

use Lucinda\DB\Wrapper;
use Lucinda\DB\Driver;
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
        return new Result($wrapper->getDriver(["a","b"]) instanceof Driver);
    }
}
