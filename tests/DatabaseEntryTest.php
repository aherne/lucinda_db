<?php
namespace Test\Lucinda\DB;
    
use Lucinda\DB\DatabaseEntry;
use Lucinda\UnitTest\Result;

class DatabaseEntryTest
{
    private $object;
    
    public function __construct()
    {
        $schema = __DIR__."/DB";
        if(!is_dir($schema)) {
            mkdir($schema, 0777);
        } else {
            $files = scandir($schema);
            foreach ($files as $file) {
                if (strpos($file, ".json")!==false) {
                    unlink($schema."/".$file);
                }
            }
        }
        
        $this->object = new DatabaseEntry($schema, ["a","b"]);
    }

    public function set()
    {
        $this->object->set(1);
        return new Result(true);
    }
        

    public function get()
    {
        return new Result($this->object->get()==1);
    }
        

    public function exists()
    {
        return new Result($this->object->exists());
    }
        

    public function increment()
    {
        return new Result($this->object->increment()==2);
    }
        

    public function decrement()
    {
        return new Result($this->object->decrement()==1);
    }
        

    public function delete()
    {
        $this->object->delete();
        return new Result(!$this->object->exists());
    }
        

}
