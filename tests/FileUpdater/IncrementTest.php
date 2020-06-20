<?php
namespace Test\Lucinda\DB\FileUpdater;

use Lucinda\DB\FileUpdater\Increment;
use Lucinda\UnitTest\Result;

class IncrementTest
{
    private $object;
    private $value = 1;
    
    public function __construct()
    {
        $this->object = new Increment("x_y");
    }
    
    public function update()
    {
        return new Result($this->object->update($this->value));
    }
    
    
    public function getValue()
    {
        return new Result($this->object->getValue()==2);
    }
    
    
}
