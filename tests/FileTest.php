<?php

namespace Test\Lucinda\DB;

use Lucinda\DB\File;
use Test\Lucinda\DB\TestCase;
use Lucinda\DB\FileUpdater;

class FileTest extends TestCase
{
    private $object;

    public function __construct()
    {
        $schema = __DIR__."/DB";
        if (!file_exists($schema)) {
            mkdir($schema, 0777);
        }
        $this->object = new File($schema."/x_y.json");
        if ($this->object->exists()) {
            $this->object->delete();
        }
    }

    public function __destruct()
    {
        rmdir(__DIR__."/DB");
    }


    public function write()
    {
        $this->object->write(["abc"=>"def"]);
        return $this->assertTrue($this->object->exists());
    }

    public function exists()
    {
        return $this->assertTrue($this->object->exists());
    }


    public function read()
    {
        return $this->assertEquals(["abc"=>"def"], $this->object->read());
    }


    public function update()
    {
        $this->object->update(
            new class () implements FileUpdater {
                public function update(&$json): bool
                {
                    $json["abc"] = "qwe";
                    return true;
                }
            }
        );
        return $this->assertEquals(["abc"=>"qwe"], $this->object->read());
    }


    public function delete()
    {
        $this->object->delete();
        return [
            $this->assertFalse($this->object->exists()),
            $this->assertFileNotExists(__DIR__."/DB/x_y.json.lock")
        ];
    }
}
