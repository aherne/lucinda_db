<?php

namespace Test\Lucinda\DB\FileUpdater;

use Lucinda\DB\FileUpdater\IndexDeleter;
use Test\Lucinda\DB\TestCase;

class IndexDeleterTest extends TestCase
{
    public function update()
    {
        $json = ["x_y"=>dirname(__DIR__)."/DB"];
        $object = new IndexDeleter("x_y");
        $object->update($json);
        return $this->assertEmptyArray($json);
    }
}
