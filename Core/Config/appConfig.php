<?php
/* -------------------------------------------------------------
 * Object Oriented PHP MVC Framework
 *  appConfig: Framework Configuration
 */
define('CURRENT_VERSION','0.93 2021-10-02');
define('COPYTIGHT','Copyright (c) 2017 - 2021 by nTak');
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
