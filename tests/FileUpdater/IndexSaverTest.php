<?php

namespace Test\Lucinda\DB\FileUpdater;

use Lucinda\DB\FileUpdater\IndexSaver;
use Test\Lucinda\DB\TestCase;

class IndexSaverTest extends TestCase
{
    public function update()
    {
        $json = [];
        $object = new IndexSaver(dirname(__DIR__)."/DB", "x_y");
        $object->update($json);
        return $this->assertEquals(dirname(__DIR__)."/DB", $json["x_y"]);
    }
}
