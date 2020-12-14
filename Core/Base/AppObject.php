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
	function __construct($owner) {
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
// initialized call
	protected function __InitClass() {
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
            $this->$event = array($Instance,$method);
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
        foreach($props as $key => $val) {
            $this->$key = $val;
        }
    }
//==============================================================================
// dynamic construct OBJECT CLass
public function __get($PropName) {
    if($this->autoload === FALSE) return NULL;
    if(isset($this->$PropName)) return $this->$PropName;
    $class_list = array(
        'Class'     => 0,
        'Model'     => 1,
        'Helper'    => 2,
        'Controller'=> 2,
    );
    if(array_key_exists($PropName,$class_list)) {
        $mod_name = $this->ModuleName;
        $cls_name = $PropName;
    } else  {
        preg_match('/[A-Z][a-z]+?$/', $PropName, $matches);
        $cls_name = $matches[0];
        if(empty($cls_name)) {
            throw new Exception("Bad name request for SubClass '{$PropName}'");
        }
        if(array_key_exists($cls_name,$class_list)) {
            $mod_name = str_replace($cls_name,'',$PropName);
        } else {
            $mod_name = $PropName;
            $cls_name = 'Model';
        }
    }
    $prop_name = "{$mod_name}{$cls_name}";
    if(class_exists($prop_name)) {
        $this->$PropName = new $prop_name($this);
        return $this->$PropName;
    }
    $fldr = array(
        ["Class"],
        ["Models","modules/{$mod_name}"],
        ["modules/{$mod_name}"],
    );
    $path_list = $fldr[$class_list[$cls_name]];
    foreach($path_list as $path) {
        $modfile = App::Get_AppPath("{$path}/{$prop_name}.php");
        if(file_exists($modfile)) {
            if($cls_name === 'Controller') {
                App::LoadModuleFiles($mod_name);    // Load on Controller + Model + Helper
                if(class_exists($prop_name)) {      // is SUCCESS?
                    $this->$PropName = new $prop_name($this);
                    return $this->$PropName;
                }
            } else {
                require_once($modfile);
                $this->$PropName = new $prop_name($this);
                return $this->$PropName;
            }
        }
    }
    // not found class file
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
