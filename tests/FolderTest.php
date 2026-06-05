<?php

namespace Test\Lucinda\DB;

use Lucinda\DB\Folder;
use Test\Lucinda\DB\TestCase;
use Lucinda\DB\FileDeleter\All;

class FolderTest extends TestCase
{
    private $folder;
    private $object;

    public function __construct()
    {
        $this->folder = __DIR__."/DBS";
        $this->object = new Folder($this->folder);
    }

    public function create()
    {
        return $this->assertTrue($this->object->create(0777));
    }


    public function exists()
    {
        return $this->assertTrue($this->object->exists());
    }


    public function isWritable()
    {
        return $this->assertTrue($this->object->isWritable());
    }


    public function scan()
    {
        file_put_contents($this->folder."/a.json", "x");
        file_put_contents($this->folder."/a.json.lock", "x");
        file_put_contents($this->folder."/temporary", "x");
        $scanner = new \Lucinda\DB\FileInspector\Counter();
        $this->object->scan($scanner);
        return $this->assertEquals(1, $scanner->getValue());
    }


    public function clear()
    {
        file_put_contents($this->folder."/a.json", "x");
        file_put_contents($this->folder."/temporary", "x");
        return $this->assertEquals(1, $this->object->clear(new All()));
    }

    public function delete()
    {
        $this->object->delete();
        return $this->assertFalse($this->object->exists());
    }
}
