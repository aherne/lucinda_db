<?php
namespace Test\Lucinda\DB\FileUpdater;
    
use Lucinda\DB\FileUpdater\IndexDeleter;
use Lucinda\UnitTest\Result;

class IndexDeleterTest
{

    public function update()
    {
        $json = ["x_y"=>"x_y"];
        $object = new IndexDeleter("x_y");
        $object->update($json);
        return new Result(empty($json));
    }
        

}
