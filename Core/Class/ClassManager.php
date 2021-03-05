<?php
/**
 * AppObjectClassの管理
 */
class ClassManager {
    private static $ObjectList = [];  // [ 'ClassName' => [ state, ini_cnt, ref_count, owner, Object] ]
//==============================================================================
public static function Create($module_name,$class_name,$owner) {
    $oname = ($owner === NULL) ? '(Main)' :$owner->ClassName;
    if(array_key_exists($module_name,static::$ObjectList)) {
        list($state,$ini,$cnt,$owlist,$obj) = static::$ObjectList[$module_name];
        if($state !== 0) {
            if(++$ini > 2 ) die("Conflict '{$module_name}' Create during initializing @ {$this->ClassName}.\n");
        } else $ini = 0;
        if(!in_array($oname,$owlist)) $owlist[] = $oname;
        static::$ObjectList[$module_name] = [ $state,$ini,++$cnt,$owlist,$obj];
        return $obj;
    }
    $obj = new $class_name($owner);
    static::$ObjectList[$module_name] = [ 1, 1, 1,[$oname], $obj];
    if(method_exists($obj,'class_startup')) {
        $obj->class_startup();
    }
    static::$ObjectList[$module_name][0] = 0;   // state off
    static::$ObjectList[$module_name][1] = 0;   // count of during initializ
    return $obj;
}
//==============================================================================
// Dump ObjectList
public static function DumpObject() {
    $dmp = "[ STATE, INI, REF ] @ OWNER\n";
    $padding = str_repeat(" ", 16);
//    ksort(static::$ObjectList);
    foreach(static::$ObjectList as $key => $val) {
        list($state,$ini,$cnt,$ow_list) = $val;
        $om = implode(', ',$ow_list);
        $kk = substr("{$key}{$padding}",0,16);
        $dmp .= "  {$kk} = [ {$state}, {$ini}, {$cnt} ]@( {$om} )\n";
    }
    return $dmp;
}

}