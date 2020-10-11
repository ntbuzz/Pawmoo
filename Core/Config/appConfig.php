<?php
/* -------------------------------------------------------------
 * PHPフレームワーク
 *  appConfig: フレームワークのコンフィグレーション
 */
define('CURRENT_VERSION','0.24.2 2020-10-11');	// フレームワークのバージョン

define('TIME_ZONE','Asia/Tokyo');	// デフォルトのタイムゾーン

// OSタイプ
if (PHP_OS == "Linux") {
	define("OSDEP","UNIX");
	define("ZIPTEMP","/tmp/");
	define('OS_CODEPAGE','UTF8');
} else {
	define("OSDEP","WIN");
	define("ZIPTEMP","C:/tmp/");
	define('OS_CODEPAGE','UTF8');
}

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