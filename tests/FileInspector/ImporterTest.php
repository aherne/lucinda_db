<?php

namespace Test\Lucinda\DB\FileInspector;

use Lucinda\DB\FileInspector\Importer;
use Lucinda\DB\Key;
use Lucinda\DB\Value;
use Lucinda\DB\Schema;
use Test\Lucinda\DB\TestCase;

class ImporterTest extends TestCase
{
    public function inspect()
    {
        // create destination schema
        $destinationSchema = dirname(__DIR__)."/DB_DESTINATION";
        mkdir($destinationSchema, 0777);

        // create and populate source schema
        $sourceSchema = dirname(__DIR__)."/DB_SOURCE";
        mkdir($sourceSchema, 0777);
        $entries = [
            ["tags"=>["a", "b"], "value"=>1],
            ["tags"=>["b", "c"], "value"=>2],
            ["tags"=>["c", "d"], "value"=>3]
        ];
        foreach ($entries as $info) {
            $key = new Key($info["tags"]);
            $object = new Value($sourceSchema, $key->getValue());
            $object->set($info["value"]);
        }

        // import to destination schema
        foreach ($entries as $info) {
            $object = new Importer($destinationSchema);
            $object->inspect($sourceSchema, implode("_", $info["tags"]).".json");
        }

        $object = new Schema($destinationSchema);
        $capacity = $object->getCapacity();
        $object->deleteAll();

        $object = new Schema($sourceSchema);
        $object->deleteAll();

        rmdir($sourceSchema);
        rmdir($destinationSchema);

        return $this->assertEquals(3, $capacity);
    }
}
