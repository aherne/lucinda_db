<?php
namespace Test\Lucinda\DB;
    
use Lucinda\DB\File;
use Lucinda\UnitTest\Result;
use Lucinda\DB\FileUpdater;

class FileTest
{
    private $object;
    
    public function __construct()
    {
        $this->object = new File(__DIR__."/DB/x_y.json");
        if ($this->object->exists()) {
            $this->object->delete();
        }
    }
    
    
    public function write()
    {
        $this->object->write(["abc"=>"def"]);
        return new Result(true);
    }

    public function exists()
    {
        return new Result($this->object->exists());
    }
        

    public function read()
    {
        return new Result($this->object->read()==["abc"=>"def"]);
    }
        

    public function update()
    {
        $this->object->update(new class implements FileUpdater {
            public function update(&$json): bool
            {
                $json["abc"] = "qwe";
                return true;
            }
        });
        return new Result($this->object->read()==["abc"=>"qwe"]);
    }
        

    public function delete()
    {
        $this->object->delete();
        return new Result(!$this->object->exists());
    }
        

}
