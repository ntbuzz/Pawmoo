<?php
/*
 * Autoloader for Database Table Creator ONLY!
 */
class SetupLoader {
    private static $LoadDirs = [];
//==============================================================================
    private static function loadClass($className) {
        foreach(static::$LoadDirs as $directory) {
            $file_name = "{$directory}/{$className}.php";
            if(is_file($file_name)) {
                require_once($file_name);
                return true;
            }
        }
//		echo "NOT FOUND:{$className}\n";
    }
//==============================================================================
// Setup専用のローダー
public static function Setup($appname) {
    static::$LoadDirs = [
        'Core/Class',
        "app/{$appname}/Config/Schema",
    ];
    spl_autoload_register(array('SetupLoader','loadClass'));
}

}
//==============================================================================
// オードローダー付き基底クラス
class AppBase {
    protected $ClassType;   // The class to which the object belongs.(Controller, Model, View. Helper)
    protected $ModuleName;  // Object module name
    protected $ClassName;   // Class Name
//==============================================================================
//	constructor( object owner )
	function __construct() {
        $this->ClassName = get_class($this);
        $this->ModuleName = preg_replace("/[A-Z][a-z]+?$/",'',$this->ClassName);
        $this->ClassType = substr($this->ClassName,strlen($this->ModuleName));
	}
//==============================================================================
//	destructor: none
	function __destruct() {
    }
//==============================================================================
//	setup objerct property
    protected function setProperty($props) {
        foreach($props as $key => $val) {
            $this->$key = $val;
        }
    }
//==============================================================================
// dynamic construct OBJECT Class for 'modules'
public function __get($PropName) {
//debug_dump(['PROP'=>$PropName]);
    if(isset($this->$PropName)) return $this->$PropName;
    $prop_name = "{$PropName}Schema";
    if(class_exists($prop_name)) {
		$this->$PropName = new $prop_name();
        return $this->$PropName;
    }
	debug_trace_info();
	die("ERROR: NOT-FOUND CLASS @ '{$prop_name}'\n\n");
    throw new Exception("SubClass Create Error for '{$prop_name}'");
}

}
function debug_trace_info() {
	$dbinfo = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT,8);    // 呼び出し元のスタックまでの数
	$trace = "";
	foreach($dbinfo as $stack) {
		if(isset($stack['file'])) {
			$path = str_replace('\\','/',$stack['file']);             // Windowsパス対策
			list($pp,$fn,$ext) = extract_path_file_ext($path);
			if($fn !== 'AppDebug') {                            // 自クラスの情報は不要
				$func = "{$fn}({$stack['line']})";
				$trace = (empty($trace)) ? $func : "{$func}>{$trace}";
			}
		}
	}
	echo "TRACE:: {$trace}\n";
}
