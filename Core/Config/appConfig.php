<?php
/* -------------------------------------------------------------
 * Object Oriented PHP MVC Framework
 *  appConfig: Framework Configuration
 */
define('CURRENT_VERSION','1.0.0pv 2022-01-25');
define('COPYTIGHT','Copyright (c) 2017 - 2022 by nTak');
define('PLATFORM_NAME','pawmoo');
define('SESSION_PREFIX','_minimvc_pawmoo_maps');

define('TIME_ZONE','Asia/Tokyo');
// Running base OS
if (PHP_OS == "Linux") {
	define("OSDEP","UNIX");
	define("ZIPTEMP","/tmp/");
} else {
	define("OSDEP","WIN");
	define("ZIPTEMP","C:/tmp/");
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
// argument of MySession
define('S_ENV',TRUE);		// App::$EnvData
define('S_REQ',FALSE);		// App::$ReqData

define('SECTION_TOKEN','<@&+*%-${[');

define('SESSION_PARAMS_CLEAR',	01);
define('SESSION_LIFE_LIMIT',	02);

define('SESSION_ENV_NOP',			0b0000);
define('SESSION_ENV_UNSET_PARAMS',	0b0001);
define('SESSION_ENV_LIFE_LIMIT',	0b0010);
define('SESSION_ENV_PICKUP_POST',	0b0100);
define('SESSION_ENV_RESERVED_1',	0b1000);
define('SESSION_ENV_EXEC_ALL',		0b1111);

define('DEFAULT_HELPER_EXPAND',		'__x');
 
define('DEFAULT_ENCRYPT_INIT','encrypt-pawmoo_iv');

class appConfig {
	// default config parameter
	public $USE_DEBUGGER	= false;
	public $SESSION_LIMIT	= 'tomorrow 03:00:00';
	public $Postgre = [];		// PostgreSQL Config
	public $SQLite = [];		// SQLite2 Config
	public $MySQL = [];			// MariaDB Config
//===============================================================
public function Setup($spec,$enviroment) {
	// config-enviroment
	$config = $spec[$enviroment];
	list($host) = explode('.',gethostname());
	// common config property
	$common_config = array_filter($config,function($v) { return !is_array($v);});
	foreach($common_config as $key => $val) $this->$key = $val;
	// select property
	$select_config = array_filter($config,function($v) { return is_array($v);});
	// extract Database Define
	foreach(['Postgre', 'SQLite', 'MySQL'] as $val) {
		// OS, Hostname, Common config Setuo
		if(array_key_exists($val,$select_config)) {
			$db_setup = $select_config[$val];
			// Common config Parameter
			$db_common = array_filter($db_setup,function($v) { return is_scalar($v);});
			$db_config = array_filter($db_setup,function($v) { return is_array($v);});
			if(!empty($db_config)) {
				// extract [ OS, Hostname] config Parameter
				foreach([PHP_OS, $host] as $db_key) {
					if(array_key_exists($db_key,$db_config)) {
						$db_common = array_merge($db_common,$db_config[$db_key]);
					}
				}
			}
			$this->$val = $db_common;
		}
	}
//print_r($this);
}

}
// GLOBAL Config Parameter
$config = new appConfig();
