<?php
require 'vendor/autoload.php';

// defines regexes to use in parameter value validation
$regexes = [
    "float"=>"/^\\d+\\.\\d+$/",
    "int"=>"/^\\d+$/",
    "string"=>"/^(.*)$/"
];

// performs console client logic
try {
    // if number of arguments is blatantly invalid, do not continue
    if (sizeof($argv)< 4) {
        throw new Exception("Number of arguments is invalid");
    }
    
    // locates method, validates parameters and executes operation
    $parameters = [];
    $reflection = new ReflectionClass("Lucinda\DB\DatabaseMaintenance");
    $reflectionMethods = $reflection->getMethods();
    foreach($reflectionMethods as $methodInfo) {
        // if method doesn't correspond to user query, no point continuing
        if($methodInfo->name!=$argv[3]) {
            continue;
        }
        
        // bind parameters from console with those of class via reflection
        $parameters = [];
        $reflectionParameters = $methodInfo->getParameters();
        if(sizeof($argv) != sizeof($reflectionParameters)+4) {
            throw new Exception("Number of parameters is invalid");
        }        
        foreach($reflectionParameters as $i=>$parameter) {
            $type = (string) $parameter->getType();
            $value = $argv[4+$i];
            if (preg_match($regexes[$type], $value) !== 1) {
                throw new Exception("Parameter is not of type: ".$type);
            }
            settype($value, $type);
            $parameters[] = $value;
        }
        
        // execute method and bind it to parameters detected
        $info = $methodInfo->invoke(new Lucinda\DB\DatabaseMaintenance($argv[1], $argv[2]), $parameters);
        fwrite(STDOUT, json_encode(["status"=>"ok", "body"=>$info])."\r\n");
        exit();
    }
        
    // if we reached this point it means method was unrecognized
    throw new Exception("Unrecognized method: ".$argv[3]);
} catch (Exception $e) {
    fwrite(STDERR, json_encode(["status"=>"error", "body"=>$e->getMessage()])."\r\n");
}