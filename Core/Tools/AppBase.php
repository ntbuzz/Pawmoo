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
		echo "NOT FOUND:{$className}\n";
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
debug_dump(['PROP'=>$PropName]);
    if(isset($this->$PropName)) return $this->$PropName;
    $prop_name = "{$PropName}Schema";
    if(class_exists($prop_name)) {
		$this->$PropName = new $prop_name();
        return $this->$PropName;
    }
    throw new Exception("SubClass Create Error for '{$prop_name}'");
}

}