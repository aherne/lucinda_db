<?php
namespace Test\Lucinda\DB;

use Lucinda\UnitTest\Result;
use Lucinda\DB\Configuration;
use Lucinda\DB\ValueDriver;

class ValueDriverTest
{
    private $object;
    
    public function __construct()
    {
        mkdir(__DIR__."/myClient1", 0777);
        mkdir(__DIR__."/myClient2", 0777);
        
        // initialize
        $this->object = new ValueDriver(new Configuration(__DIR__."/tests.xml", "local"), ["a","b"]);
    }
    
    public function __destruct()
    {
        rmdir(__DIR__."/myClient1");
        rmdir(__DIR__."/myClient2");
    }
    
    public function set()
    {
        $output = [];
        $this->object->set(1);
        $output[] = new Result($this->test('myClient1', 1) && $this->test('myClient2', 1));
        return $output;
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
        return new Result($this->object->increment()==2 && $this->object->get()==2);
    }
    
    
    public function decrement()
    {
        return new Result($this->object->decrement()==1 && $this->object->get()==1);
    }
    
    
    public function delete()
    {
        $this->object->delete();
        return new Result(!$this->object->exists() && !$this->test('myClient1', 1) && !$this->test('myClient2', 1));
    }
    
    private function test(string $folder, $value): bool
    {
        $filename = __DIR__."/".$folder."/a_b.json";
        if (!file_exists($filename)) {
            return false;
        }
        
        $content = json_decode(file_get_contents($filename), true);
        return $content == $value;
    }
}
