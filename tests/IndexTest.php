<?php
namespace Test\Lucinda\DB;

use Lucinda\DB\Index;
use Lucinda\UnitTest\Result;

class IndexTest
{
    private $object;
    
    public function __construct()
    {
        $this->object = new Index(__DIR__."/DB", "test");
    }
    
    public function add()
    {
        $this->object->add(__DIR__."/DB", "test_me");
        return new Result(true);
    }
        

    public function exists()
    {
        return new Result($this->object->exists());
    }
        

    public function get()
    {
        return new Result($this->object->get() == ["test_me"=>__DIR__."/DB"]);
    }
        

    public function remove()
    {
        $this->object->remove("test_me");
        return new Result($this->object->get() == []);
    }
        

    public function delete()
    {
        $this->object->delete();
        return new Result(!$this->object->exists());
    }
}
