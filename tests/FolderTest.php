<?php
namespace Test\Lucinda\DB;

use Lucinda\DB\Folder;
use Lucinda\UnitTest\Result;
use Lucinda\DB\FileDeleter\All;

class FolderTest
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
        return new Result($this->object->create(0777));
    }
        

    public function exists()
    {
        return new Result($this->object->exists());
    }
        

    public function isWritable()
    {
        return new Result($this->object->isWritable());
    }
    
    
    public function scan()
    {
        file_put_contents($this->folder."/a.json", "x");
        $scanner = new \Lucinda\DB\FileInspector\Counter();
        $this->object->scan($scanner);
        return new Result($scanner->getValue()==1);
    }
        

    public function clear()
    {
        file_put_contents($this->folder."/a.json", "x");
        return new Result($this->object->clear(new All())==1);
    }
    
    public function delete()
    {
        $this->object->delete();
        return new Result(!$this->object->exists());
    }
}
