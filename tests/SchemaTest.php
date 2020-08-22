<?php
namespace Test\Lucinda\DB;
    
use Lucinda\DB\Schema;
use Lucinda\UnitTest\Result;
use Lucinda\DB\Value;
use Lucinda\DB\Key;

class SchemaTest
{
    private $schema;
    private $object;
    
    public function __construct()
    {
        $this->schema = __DIR__."/DB";
        if(is_dir($this->schema)) {
            $files = scandir($this->schema);
            foreach ($files as $file) {
                if (!in_array($file, [".",".."])) {
                    unlink($this->schema."/".$file);
                }
            }
            rmdir($this->schema);
        }
        
        $this->object = new Schema($this->schema);
    }
    
    
    public function create()
    {
        return new Result($this->object->create());
    }

    public function exists()
    {
        return new Result($this->object->exists());
    }
        

    public function getCurrentCapacity()
    {
        $entries = [
            ["tags"=>["a", "b"], "value"=>1],
            ["tags"=>["b", "c"], "value"=>2],
            ["tags"=>["c", "d"], "value"=>3]
        ];
        foreach ($entries as $info) {
            $key = new Key($info["tags"]);
            $object = new Value($this->schema, $key->getValue());
            $object->set($info["value"]);
        }
        return new Result($this->object->getCurrentCapacity()==3);
    }
        

    public function deleteAll()
    {
        return new Result($this->object->deleteAll()==3);
    }
        

    public function deleteUntil()
    {
        $entries = [
            ["tags"=>["a", "b"], "value"=>1, "date"=>"2018-01-02 01:02:03"],
            ["tags"=>["b", "c"], "value"=>2, "date"=>"2018-02-03 04:05:06"],
            ["tags"=>["c", "d"], "value"=>3, "date"=>"2018-03-04 07:08:09"],
            ["tags"=>["d", "e"], "value"=>3, "date"=>"2018-04-05 10:11:12"]
        ];
        foreach ($entries as $info) {
            $key = new Key($info["tags"]);
            $object = new Value($this->schema, $key->getValue());
            $object->set($info["value"]);
            touch($this->schema."/".implode("_", $info["tags"]).".json", strtotime($info["date"]));
        }
        return new Result($this->object->deleteUntil(strtotime("2018-01-23 10:11:22"))==1);
    }
        

    public function deleteByTag()
    {
        return new Result($this->object->deleteByTag("c")==2);
    }
        

    public function deleteByCapacity()
    {
        $this->object->deleteAll();
        $entries = [
            ["tags"=>["a", "b"], "value"=>1, "date"=>"2018-01-02 01:02:03"],
            ["tags"=>["b", "c"], "value"=>2, "date"=>"2018-02-03 04:05:06"],
            ["tags"=>["c", "d"], "value"=>3, "date"=>"2018-03-04 07:08:09"],
            ["tags"=>["d", "e"], "value"=>4, "date"=>"2018-04-05 10:11:12"],
            ["tags"=>["e", "f"], "value"=>5, "date"=>"2018-05-05 10:11:12"],
            ["tags"=>["f", "g"], "value"=>6, "date"=>"2018-06-05 10:11:12"],
            ["tags"=>["g", "h"], "value"=>7, "date"=>"2018-07-05 10:11:12"],
            ["tags"=>["h", "i"], "value"=>8, "date"=>"2018-08-05 10:11:12"],
            ["tags"=>["i", "j"], "value"=>9, "date"=>"2018-09-05 10:11:12"],
            ["tags"=>["j", "k"], "value"=>10, "date"=>"2018-10-05 10:11:12"]
        ];
        foreach ($entries as $info) {
            $key = new Key($info["tags"]);
            $object = new Value($this->schema, $key->getValue());
            $object->set($info["value"]);
            touch($this->schema."/".implode("_", $info["tags"]).".json", strtotime($info["date"]));
        }
        return new Result($this->object->deleteByCapacity(4, 8)==4);
    }
        

}
