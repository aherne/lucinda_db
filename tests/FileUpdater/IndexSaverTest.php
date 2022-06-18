<?php

namespace Test\Lucinda\DB\FileUpdater;

use Lucinda\DB\FileUpdater\IndexSaver;
use Lucinda\UnitTest\Result;

class IndexSaverTest
{
    public function update()
    {
        $json = [];
        $object = new IndexSaver(dirname(__DIR__)."/DB", "x_y");
        $object->update($json);
        return new Result($json["x_y"] == dirname(__DIR__)."/DB");
    }
}
