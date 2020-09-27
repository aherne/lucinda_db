<?php
namespace Test\Lucinda\DB\FileInspector;

use Lucinda\DB\FileInspector\ByTag;
use Lucinda\DB\Key;
use Lucinda\UnitTest\Result;

class ByTagTest
{
    private $inspector;
    
    public function __construct()
    {
        $this->inspector = new ByTag("a");
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
        return new Result($this->inspector->getEntries()==["a_b.json"]);
    }
}
