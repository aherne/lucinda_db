<?php
namespace Test\Lucinda\DB\FileInspector;

use Lucinda\DB\FileInspector\All;
use Lucinda\DB\Key;
use Lucinda\UnitTest\Result;

class AllTest
{
    private $inspector;
    
    public function __construct()
    {
        $this->inspector = new All();
    }
    
    public function inspect()
    {
        $schema = dirname(__DIR__)."/DB";
        $entries = [
            ["a", "b"],
            ["b", "c"],
        ];
        foreach ($entries as $info) {
            $object = new Key($info);
            $this->inspector->inspect($schema, $object->getValue().".json");
        }
        return new Result(true);
    }
    
    
    public function getEntries()
    {
        return new Result($this->inspector->getEntries()==["a_b", "b_c"]);
    }
}
