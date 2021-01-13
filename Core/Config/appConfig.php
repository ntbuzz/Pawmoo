<?php
/* -------------------------------------------------------------
 * Object Oriented PHP MVC Framework
 *  appConfig: Framework Configuration
 */
<<<<<<< HEAD
define('CURRENT_VERSION','0.43.0 2020-12-25');
define('COPYTIGHT','Copyright (c) 2017 - 2020 by nTak');
=======
define('CURRENT_VERSION','0.44.0 2021-01-13');
define('COPYTIGHT','Copyright (c) 2017 - 2021 by nTak');
>>>>>>> 565034cca05e05e90f5dd8b0aaad125030023576
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

const IgnoreFiles =[
		".","..","Thumbs.db","web.config","desktop.ini","files.txt",
		'$RECYCLE.BIN','System Volume Information'
];
if(php_sapi_name() === 'cli') {
	global $argv;
	define('CLI_DEBUG',TRUE);
} else {
	define('CLI_DEBUG',FALSE);
}
//	define('CLI_ARGV',$argv);
const CLI_ARGV = array();
// argument of MySession
define('S_ENV',TRUE);		// App::$EnvData
define('S_REQ',FALSE);		// App::$ReqData

define('SECTION_TOKEN','<@&+*%-${[');
