<?php

namespace Test\Lucinda\DB;

use Test\Lucinda\DB\TestCase;
use Lucinda\DB\Configuration;
use Lucinda\DB\ValueDriver;

class ValueDriverTest extends TestCase
{
    private $object;

    public function __construct()
    {
        mkdir(__DIR__."/myClient1", 0777);
        mkdir(__DIR__."/myClient2", 0777);

        // initialize
        $configuration = new Configuration(__DIR__."/tests.xml", "local");
        $this->object = new ValueDriver($configuration->getSchemas(), ["a","b"]);
    }

    public function __destruct()
    {
        rmdir(__DIR__."/myClient1");
        rmdir(__DIR__."/myClient2");
    }

    public function set()
    {
        $this->object->set(1);
        return [
            $this->assertEquals(1, $this->readValue('myClient1')),
            $this->assertEquals(1, $this->readValue('myClient2'))
        ];
    }


    public function get()
    {
        return $this->assertEquals(1, $this->object->get());
    }

    public function getRepairsCorruptedReplica()
    {
        $this->object->set(1);
        file_put_contents(__DIR__."/myClient1/a_b.json", "{");
        $value = $this->object->get();
        $repaired = json_decode(file_get_contents(__DIR__."/myClient1/a_b.json"), true);
        $this->object->delete();
        return [
            $this->assertEquals(1, $value),
            $this->assertEquals(1, $repaired)
        ];
    }


    public function exists()
    {
        return $this->assertTrue($this->object->exists());
    }


    public function increment()
    {
        return [
            $this->assertEquals(2, $this->object->increment()),
            $this->assertEquals(2, $this->object->get())
        ];
    }


    public function decrement()
    {
        return [
            $this->assertEquals(1, $this->object->decrement()),
            $this->assertEquals(1, $this->object->get())
        ];
    }


    public function delete()
    {
        $this->object->delete();
        return [
            $this->assertFalse($this->object->exists()),
            $this->assertFileNotExists(__DIR__."/myClient1/a_b.json"),
            $this->assertFileNotExists(__DIR__."/myClient2/a_b.json")
        ];
    }

    private function readValue(string $folder): mixed
    {
        $filename = __DIR__."/".$folder."/a_b.json";
        if (!file_exists($filename)) {
            return null;
        }

        return json_decode(file_get_contents($filename), true);
    }
}
