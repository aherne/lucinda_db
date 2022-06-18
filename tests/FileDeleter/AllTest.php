<?php

namespace Test\Lucinda\DB\FileDeleter;

use Lucinda\DB\FileDeleter\All;
use Lucinda\UnitTest\Result;

class AllTest
{
    public function delete()
    {
        $schema = dirname(__DIR__)."/DB";
        mkdir($schema, 0777);
        $file = "x_y.json";
        file_put_contents($schema."/".$file, 1);
        $object = new All();
        $status = $object->delete($schema, $file);
        rmdir($schema);
        return new Result($status);
    }
}
