<?php
/* -------------------------------------------------------------
 * Object Oriented PHP MVC Framework
 *  appConfig: Framework Configuration
 */
define('CURRENT_VERSION','2.0.4 2022-04-26');
define('COPYTIGHT','Copyright (c) 2017 - 2022 by nTak');
define('PLATFORM_NAME','pawmoo');
define('SESSION_PREFIX','_minimvc_pawmoo_maps');

define('TIME_ZONE','Asia/Tokyo');
// Running base OS
if (PHP_OS == "Linux") {
	define("OSDEP","UNIX");
	define("ZIPTEMP","/tmp/");
	define('SESSION_SAVE_PATH','/var/lib/php/session');	// same as PHP default
//	define('SESSION_SAVE_PATH','/var/tmp/pawmoo/session');	// not-writable
} else {
	define("OSDEP","WIN");
	define("ZIPTEMP","C:/tmp/");
	define('SESSION_SAVE_PATH','c:/Windows/temp/pawmoo');
}
define('OS_CODEPAGE','UTF8');
//	define('OS_CODEPAGE','SJIS');

const IgnoreFiles =[
		".","..","Thumbs.db","web.config","desktop.ini","files.txt",
		'$RECYCLE.BIN','System Volume Information'
];
if(php_sapi_name() === 'cli') {
	define('CLI_DEBUG',true);
} else {
	define('CLI_DEBUG',FALSE);
}
// // argument of MySession
// define('S_ENV',TRUE);		// App::$EnvData
// define('S_REQ',FALSE);		// App::$ReqData

// define('SECTION_TOKEN','<@&+*%-${[');

// define('SESSION_PARAMS_CLEAR',	01);
// define('SESSION_LIFE_LIMIT',	02);

define('SESSION_ENV_NOP',			0b0000);
define('SESSION_ENV_UNSET_PARAMS',	0b0001);
define('SESSION_ENV_LIFE_LIMIT',	0b0010);
define('SESSION_ENV_PICKUP_POST',	0b0100);
define('SESSION_ENV_RESERVED_1',	0b1000);
define('SESSION_ENV_EXEC_ALL',		0b1111);

define('DEFAULT_HELPER_EXPAND',		'__x');
 
define('DEFAULT_ENCRYPT_INIT','encrypt-pawmoo_iv');

define('REBUILD_MARK',	're-build-mark');
/*
GlobalConfig structure
array(
	// common config parameter
	define-name	=> value,
	...
	// common database parameter, DB_NAME is (Postgre,SQLite)
	DB_NAME => [
		DB_PARAMS...
		// OS or HOSTNAME Dependent parameter
		PHP_OS	=> [ DB_PARAMS... ]		// PHP_OS is (Linux or WINNT)
		HOST	=> [ DB_PARAMS... ]		// HOST is gethostname()
	],
	// enviroment parameter
	production => [ // same as common config structure // ]
	development=> [ // same as common config structure // ]
)
*/
class appConfig {
	// default config parameter
	public $Enviroment		= "";
	public $USE_DEBUGGER	= false;
	public $SESSION_LIMIT	= 'tomorrow 03:00:00';
	private $HandlerList = [ 'Postgre', 'SQLite'];
//===============================================================
private function database_Setup($host,$config) {
	foreach($this->HandlerList as $val) {
		// OS, Hostname, Common config Setuo
		if(array_key_exists($val,$config)) {
			$db_setup = $config[$val];
			// Common config Parameter
			$db_common = array_filter($db_setup,function($v) { return is_scalar($v);});
			$db_config = array_filter($db_setup,function($v) { return is_array($v);});
			if(!empty($db_config)) {
				// extract [ OS, Hostname] config Parameter
				foreach([PHP_OS, $host] as $db_key) {
					if(array_key_exists($db_key,$db_config)) {
						$db_common = array_override($db_common,$db_config[$db_key]);
					}
				}
			}
			$this->$val = array_override($this->$val, $db_common);
		}
	}
}
//===============================================================
public function dumpEnviroment() {
 	sysLog::stderr(['ENV'=>$this->Enviroment]);
	foreach($this->HandlerList as $val) {
		if(isset($this->$val)) sysLog::stderr([$val => $this->$val]);
	}
}
//===============================================================
public function Setup($spec,$enviroment) {
	list($host) = explode('.',gethostname());		// exclude domain-name
	// Create HandlerList parameter by empty value
	if(defined('HANDLER_LIST')) $this->HandlerList = HANDLER_LIST;
	foreach($this->HandlerList as $val) $this->$val = [];
	// setup Global-Config for Database
	$this->database_Setup($host,$spec);
	// check enviroment config
	if(array_key_exists($enviroment,$spec)) {
		$config = $spec[$enviroment];
		// common config property
		$common_config = array_filter($config,function($v) { return !is_array($v);});
		foreach($common_config as $key => $val) $this->$key = $val;
		// database select property
		$select_config = array_filter($config,function($v) { return is_array($v);});
		$this->database_Setup($host,$select_config);
		$this->Enviroment = $enviroment;
	}
}

}
// GLOBAL Config Parameter
$config = new appConfig();
