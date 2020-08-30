<?php
namespace Test\Lucinda\DB;

use Lucinda\DB\Configuration;
use Lucinda\UnitTest\Result;

class ConfigurationTest
{
    private $object;
    
    public function __construct()
    {
        $this->object = new Configuration(simplexml_load_string('
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
    }
    

    public function getMasterSchema()
    {
        return new Result($this->object->getMasterSchema() == "myMaster");
    }
        

    public function getDistributionType()
    {
        return new Result($this->object->getDistributionType() == "distributed");
    }
        

    public function getSlaveSchemas()
    {
        return new Result($this->object->getSlaveSchemas() == ["myClient1", "myClient2"]);
    }
}
