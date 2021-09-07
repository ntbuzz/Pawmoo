<?php
/* -------------------------------------------------------------
 * Object Oriented PHP MVC Framework
 *  AppObject:    The object class on which all classes are based.
 *                Hold inheritance information and class information.
 */
//==============================================================================
class AppObject {
    protected $AOwner;      // Object Owner
    protected $ClassType;   // The class to which the object belongs.(Controller, Model, View. Helper)
    protected $ModuleName;  // Object module name
    protected $ClassName;   // Class Name
    protected $LocalePrefix;    // Language Prefix(en,ja,...)
    protected $autoload = TRUE; // autoload on __get() magic method
//==============================================================================
//	constructor( object owner )
	function __construct($owner = NULL) {
        $this->AOwner = $owner;
        $this->ClassName = get_class($this);
        $this->ModuleName = preg_replace("/[A-Z][a-z]+?$/",'',$this->ClassName);
        $this->ClassType = substr($this->ClassName,strlen($this->ModuleName));
        // Call on Base-Class construct, Use parent Moddule Name divert.
        if($this->ClassName == "App{$this->ClassType}") $this->ModuleName = $owner->ModuleName;
        $this->LocalePrefix = ($owner===NULL) ? $this->ModuleName : $owner->LocalePrefix;
	}
//==============================================================================
//	destructor: none
	function __destruct() {
    }
//==============================================================================
//	Class-Initialize after __construct()
	public function class_startup() {
        $this->class_initialize();
	}
//==============================================================================
// initialized call
	protected function class_initialize() {
        if(method_exists($this,'ClassInit')) {
            $this->ClassInit();
        }
	}
//==============================================================================
//	set on EVENT function
//==============================================================================
    protected function setEvent($event,$Instance,$method) {
        list($class,$ev) = explode('.',$event);
        if(empty($ev)) {
            $this->$event = [$Instance,$method];
        } else {
            $this->$class->setEvent($ev,$Instance,$method);
        }
	}
//==============================================================================
// Fires if the event function is registered.
//==============================================================================
    public function doEvent($event, $args) {
        if(is_array($this->$event)) {      // event callback function
            list($Instance,$method) = $this->$event;
            if(method_exists($Instance,$method)) {
                $Instance->$method($args);
            }
        }
    }
//==============================================================================
//	setup objerct property
    protected function setProperty($props) {
		debug_log(DBMSG_DEBUG,['SET-PROPERTY'=>['MODULE'=>$this->ClassName,'PROP'=>$props]]);
        foreach($props as $key => $val) {
            $this->$key = $val;
        }
    }
//==============================================================================
// dynamic construct OBJECT Class for 'modules'
public function __get($PropName) {
    if($this->autoload === FALSE) return NULL;
    if(isset($this->$PropName)) return $this->$PropName;
    $class_names = [
        'Model'         =>  -5,
        'Controller'    => -10,
    ];
    if(array_key_exists($PropName,$class_names)) {
        $mod_name = $this->ModuleName;
        $cls_name = $PropName;
    } else  {
        $mod_name = $PropName;
        $cls_name = 'Model';
        foreach($class_names as $c_name => $len) {
            if($c_name === substr($PropName,$len)) {
                $mod_name = substr($PropName,0,$len);
                $cls_name = $c_name;
                break;
            }
        }
    }
    // already class file loaded?
    $prop_name = "{$mod_name}{$cls_name}";
    if(class_exists($prop_name)) {
        $this->$PropName = ClassManager::Create($prop_name,$prop_name,$this);
        return $this->$PropName;
    }
    // app/modules/* path search
    $modfile = App::Get_AppPath("modules/{$mod_name}/{$prop_name}.php");
    if(file_exists($modfile)) {
        if($cls_name === 'Controller') {
            App::LoadModuleFiles($mod_name);    // Load on Controller,Model,Helper
            if(class_exists($prop_name)) {      // is SUCCESS?
                // Controller create with Model,View.Helper
                $this->$PropName = ClassManager::Create($prop_name,$prop_name,$this);
                return $this->$PropName;
            }
        } else {
            require_once($modfile);
            $this->$PropName = ClassManager::Create($prop_name,$prop_name,$this);
            return $this->$PropName;
        }
    }
    throw new Exception("SubClass Create Error for '{$prop_name}'");
}
//==============================================================================
// read LOCALE resource.
// if allow_array will be TRUE, read the Array allowed.
public function _($defs, $allow_array = FALSE) {
    return LangUI::get_value($this->LocalePrefix, $defs, $allow_array);
}
//==============================================================================
protected function __($defs, $allow_array = FALSE) {
    return LangUI::get_value('core', $defs, $allow_array);
}
//==============================================================================
// read LOCALE resource by array item
public function _in($arr,$defs) {
    return LangUI::get_array($arr, $this->LocalePrefix, $defs);
}
//==============================================================================
protected function __in($arr,$defs) {
    return LangUI::get_array($arr, 'core', $defs);
}

}
