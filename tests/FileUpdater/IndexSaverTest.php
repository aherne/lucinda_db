<?php
namespace Test\Lucinda\DB\FileUpdater;
    
use Lucinda\DB\FileUpdater\IndexSaver;
use Lucinda\UnitTest\Result;

class IndexSaverTest
{

    public function update()
    {
        $json = [];
        $object = new IndexSaver("x_y");
        $object->update($json);
        return new Result($json["x_y"] == "x_y");
    }
        

}
