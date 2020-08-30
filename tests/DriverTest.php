<?php
namespace Test\Lucinda\DB;

use Lucinda\DB\Value;
use Lucinda\UnitTest\Result;
use Lucinda\DB\Key;
use Lucinda\DB\Configuration;
use Lucinda\DB\Driver;

class DriverTest
{
    private $object1;
    private $object2;
    
    public function __construct()
    {
        // cleanup
        $schemas = ["dbm1", "dbm2", "dbc11", "dbc12", "dbc21", "dbc22"];
        foreach ($schemas as $schema) {
            $schema = __DIR__."/".$schema;
            if (!is_dir($schema)) {
                mkdir($schema, 0777);
            } else {
                $files = scandir($schema);
                foreach ($files as $file) {
                    if (strpos($file, ".json")!==false) {
                        unlink($schema."/".$file);
                    }
                }
            }
        }
        
        // initialize
        $this->object1 = new Driver(new Configuration(simplexml_load_string('
<xml>
    <ldb>
        <local>
            <schemas master="'.__DIR__.'/dbm1" type="distributed">
                <slave>'.__DIR__.'/dbc11</slave>
                <slave>'.__DIR__.'/dbc12</slave>
            </schemas>
        </local>
    </ldb>
</xml>
'), "local"), ["a","b"]);
        $this->object2 = new Driver(new Configuration(simplexml_load_string('
<xml>
    <ldb>
        <local>
            <schemas master="'.__DIR__.'/dbm2" type="mirrored">
                <slave>'.__DIR__.'/dbc21</slave>
                <slave>'.__DIR__.'/dbc22</slave>
            </schemas>
        </local>
    </ldb>
</xml>
'), "local"), ["a","b"]);
    }
    
    public function set()
    {
        $output = [];
        $this->object1->set(1);
        $output[] = new Result($this->test('dbm1', 1) && $this->test('dbc11', 1), "distributed");
        $this->object2->set(1);
        $output[] = new Result($this->test('dbm2', 1) && $this->test('dbc21', 1) && $this->test('dbc22', 1), "mirrored");
        return $output;
    }
    
    
    public function get()
    {
        $output = [];
        $output[] = new Result($this->object1->get()==1, "distributed");
        $output[] = new Result($this->object2->get()==1, "mirrored");
        return $output;
    }
    
    
    public function exists()
    {
        $output = [];
        $output[] = new Result($this->object1->exists(), "distributed");
        $output[] = new Result($this->object2->exists(), "mirrored");
        return $output;
    }
    
    
    public function increment()
    {
        $output = [];
        $output[] = new Result($this->object1->increment()==2 && $this->object1->get()==2, "distributed");
        $output[] = new Result($this->object2->increment()==2 && $this->object2->get()==2, "mirrored");
        return $output;
    }
    
    
    public function decrement()
    {
        $output = [];
        $output[] = new Result($this->object1->decrement()==1 && $this->object1->get()==1, "distributed");
        $output[] = new Result($this->object2->decrement()==1 && $this->object2->get()==1, "mirrored");
        return $output;
    }
    
    
    public function delete()
    {
        $output = [];
        $this->object1->delete();
        $output[] = new Result(!$this->object1->exists() && !$this->test('dbm1', 1) && !$this->test('dbc11', 1), "distributed");
        $this->object2->delete();
        $output[] = new Result(!$this->object2->exists() && !$this->test('dbm2', 1) && !$this->test('dbc21', 1) && !$this->test('dbc22', 1), "mirrored");
        return $output;
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
