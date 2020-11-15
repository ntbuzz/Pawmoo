<?php
/* -------------------------------------------------------------
 * Object Oriented PHP MVC Framework
 *  appConfig: Framework Configuration
 */
define('CURRENT_VERSION','0.33.0 2020-11-14');
define('COPYTIGHT','Copyright (c) 2017 - 2020 by nTak');
define('PLATFORM_NAME','Radweapst');

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

const IgnoreFiles =[
		".","..","Thumbs.db","web.config","desktop.ini","files.txt",
		'$RECYCLE.BIN','System Volume Information'
];
if(php_sapi_name() === 'cli') {
	global $argv;
	define('CLI_DEBUG',TRUE);
	define('CLI_ARGV',$argv);
} else {
	define('CLI_DEBUG',FALSE);
}
// argument of MySession
define('S_ENV',TRUE);		// App::$EnvData
define('S_REQ',FALSE);		// App::$ReqData

define('SINGLE_TOKEN','&@-<');
define('SECTION_TOKEN','<@&+*%-${[');
