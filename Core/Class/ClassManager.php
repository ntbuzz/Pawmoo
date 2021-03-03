<?php
/**
 * AppObjectClassの管理
 */
class ClassManager {
    private static $ObjectList = [];  // [ 'ClassName' => [ state, Object, count ] ]
//==============================================================================
public static function NewClass($class_name,$owner) {
    if(array_key_exists($class_name,static::$ObjectList)) {
        list($state,$obj,$cnt) = static::$ObjectList[$class_name];
        static::$ObjectList[$class_name][2]++;
        return $obj;
    }
//    if($class_name === 'IndexModel') echo "INDEX: @ {$owner->ClassName}\n";
    $obj = new $class_name($owner);
//echo gettype($obj)."<br>\n";
    static::$ObjectList[$class_name] = [ 1, $obj,1];
    if(method_exists($obj,'class_startup')) {
        $obj->class_startup();
    }
    return $obj;
}
//==============================================================================
public static function getObjectList() {
    return static::$ObjectList;
}
//==============================================================================
public static function DumpObject() {
    $dmp = "\n";
    $padding = str_repeat(" ", 16);
    foreach(static::$ObjectList as $key => $val) {
        list($state,$obj,$cnt) = $val;
        $kk = substr("{$key}{$padding}",0,16);
        $dmp .= "  {$kk} = [ {$state}, {$obj->ModuleName}, {$cnt} ]\n";
    }
    return $dmp;
}

}